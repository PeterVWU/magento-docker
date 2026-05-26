# ECOM-78 Source Media Export and GCS Path Validation

Status: Complete

## Goal

Validate that media references imported by the ECOM-7 rehearsal resolve through
the GCS-backed Magento media path in staging.

This ticket covers source media input, copy procedure, and path validation for:

- product gallery and image-attribute files;
- category image files attached to retained products;
- customer attachment files that represent retained customer-license media.

It does not decide the final retained module behavior for customer attachments
or customer-license workflows. That remains tied to ECOM-77 and ECOM-55.

## Current Finding

The checked-out legacy reference tree contains only placeholder `pub/media`
directories and `.htaccess` files. It does not contain product/category/customer
media binaries.

The approved source media bucket is:

```text
project: vapewholesaleusa
bucket:  media-m5844
root:    gs://media-m5844/media/
```

That GCS prefix maps to the legacy Magento `pub/media` root. It contains
expected Magento media directories such as `catalog/`, `customer/`,
`customer_address/`, `import/`, `wysiwyg/`, and generated thumbnail/cache
directories.

The first local path export from the loaded ECOM-7 source slice produced:

| Media family | Rows |
| --- | ---: |
| `product_gallery` | 1,741 |
| `product_image` | 490 |
| `product_small_image` | 489 |
| `product_swatch_image` | 340 |
| `product_thumbnail` | 490 |
| `customer_attachment` | blocked: source table missing |

No category media rows were returned for the current retained product category
set. Running the copy-plan check against the checked-out legacy `pub/media`
found zero required binaries and 3,551 missing inputs.

After the approved source bucket was identified, the GCS-to-GCS copy from
`gs://media-m5844/media/` to `gs://vwu-magento-stg-media/media/` completed with:

| Result | Count |
| --- | ---: |
| Unique required paths | 1,741 |
| Copied to staging | 1,245 |
| Missing from source bucket | 496 |
| Copy failures | 0 |

The copied-report paths were compared back against the staging
`media/catalog/product/` object listing, and all 1,245 copied paths were present
in the target bucket. A 10-object sample of copied product media was then probed
through `https://uat.vapewholesaleusa.com/media/...`; all 10 returned HTTP 200.

The 496 source-missing paths are accepted as a non-blocking source data gap for
this ticket. `ECOM-88` was canceled after owner review.

Representative UAT GraphQL validation used 10 SKUs with copied media. The
`products` query returned 8 matching products and 3 unique media URLs from
`image`, `small_image`, `thumbnail`, and `media_gallery` fields. All 3 returned
HTTP 200.

Admin Media Gallery required an additional runtime fix after the initial copy:
the staging web/worker containers had `MAGENTO_MEDIA_BUCKET` but did not write
Magento `remote_storage` config into generated `app/etc/env.php`. The runtime
entrypoint now writes `gcs-s3` remote storage config and the staging VMs fetch a
GCS HMAC key/secret from Secret Manager.

For the staging bucket layout, `remote_storage.prefix` is intentionally empty.
Magento's media filesystem adds the `media/` directory segment, so the effective
GCS object path for `catalog/product/a/b/example.jpg` is:

```text
gs://vwu-magento-stg-media/media/catalog/product/a/b/example.jpg
```

Using `remote_storage.prefix = media` made Magento look under an extra media
path level and caused `media-gallery:sync` to see zero files.

Magento's default Media Gallery allowlist includes `catalog/category` but not
`catalog/product`. The `Vapewholesaleusa_GcsRemoteStorage` module now adds
`catalog/product` to
`system/media_storage_configuration/allowed_resources/media_gallery_image_folders`
so imported product images can appear in Admin Media Gallery. After deploying
image `ecom78-media-gallery-product-202605221724`, staging validation showed:

| Admin Media Gallery check | Result |
| --- | ---: |
| Magento media filesystem entries under `catalog/product` | 1,408 |
| Sync-eligible product image files | 1,245 |
| `media_gallery_asset` rows after `media-gallery:sync` | 1,245 |

Sample synced asset paths:

```text
catalog/product/0/0/00_8dabed.jpg
catalog/product/0/0/00_8dabed_1.jpg
catalog/product/0/1/01-strawberry_watermelon.png
catalog/product/0/2/02-strawberry_kiwi_ice.png
catalog/product/0/3/03-white_peach_raspberry.png
```

Customer-license media was not validated in this pass because the loaded source
extract does not include the legacy `customer_attachment` table. That extension
data remains part of the ECOM-77/ECOM-55 retained-module path rather than a
blocker for this product-media copy.

Expected approved source root:

```text
gs://media-m5844/media/
  catalog/product/...
  catalog/category/...
  customer_attachment/...
```

Keep the source media root and all generated reports under ignored,
access-restricted local paths. Do not commit media binaries, production-derived
manifests, object listings, customer names, or signed URLs.

## Artifact Layout

Use this ignored local directory for the ECOM-78 run:

```text
var/import/production-derived/ecom78-media/
  source-media-paths.tsv
  media-manifest.tsv
  copy-list.txt
  missing-media.tsv
  gcs-object-list-before.tsv
  gcs-object-list-after.tsv
  validation-notes.md
```

## Source Path Export

Run the ECOM-7 source selection first so `ecom7_product_sample` and
`ecom7_customer_sample` exist in the source schema:

```bash
cat docs/ecom-7/source-selection.sql docs/ecom-7/dataset-assembly.sql \
  | docker compose exec -T db mysql -uroot -pmagento-root -D vusa_db0
```

Then export the combined media-path TSV:

```bash
scripts/ecom78/export-source-media-paths
```

That script writes:

```text
var/import/production-derived/ecom78-media/source-media-paths.tsv
var/import/production-derived/ecom78-media/source-media-path-summary.tsv
```

The maintained export query lives in
[`scripts/ecom78/export-source-media-paths`](../../scripts/ecom78/export-source-media-paths).
The TSV uses this column shape:

```text
media_family	entity_kind	entity_key	media_path	raw_value
```

If `customer_attachment` is unavailable in the source extract, the script emits
a `CUSTOMER_ATTACHMENT_TABLE_MISSING` marker row. Record that as a blocked
customer-license media input. Do not silently mark customer-license media as
validated.

## Local Source Media Check

Build the copy plan from the path export and approved source media root:

```bash
scripts/ecom78/build-media-copy-plan \
  var/import/production-derived/ecom78-media/source-media-paths.tsv \
  /path/to/source/pub/media
```

Review:

- `media-manifest.tsv`: every required path and found/missing status;
- `missing-media.tsv`: paths that need cleanup tickets or source re-export;
- `copy-list.txt`: relative files safe to copy from `SOURCE_MEDIA_ROOT`.

No GCS copy should start until the missing report is reviewed. For this run,
owner review accepted the missing source objects as non-blocking.

## GCS Copy Procedure

Use the same object-prefix convention as the staging Magento remote-storage
configuration. In the current staging layout, Magento remote storage uses bucket
`vwu-magento-stg-media` with an empty remote-storage prefix, and Magento's media
directory contributes the `media/` object prefix. The copied object for
`catalog/product/a/b/example.jpg` must land at:

```text
gs://vwu-magento-stg-media/media/catalog/product/a/b/example.jpg
```

Recommended copy command after manifest review:

```bash
awk -F '\t' 'NR > 1 && $4 != "" { print $4 }' \
  var/import/production-derived/ecom78-media/source-media-paths.tsv \
  | sort -u \
  > var/import/production-derived/ecom78-media/gcs-required-paths.txt

CLOUDSDK_CONFIG=/tmp/gcloud-vwu \
  scripts/ecom78/copy-gcs-media-from-list \
    var/import/production-derived/ecom78-media/gcs-required-paths.txt \
    gs://media-m5844/media \
    gs://vwu-magento-stg-media/media
```

Use environment-specific bucket and prefix values from staging configuration,
not hard-coded examples. Preserve relative paths under `pub/media`; do not add
an extra `pub/media` or `media` directory level inside the Magento media prefix.

Before and after the copy, capture object counts under the target prefixes:

```bash
gsutil ls -r gs://vwu-magento-stg-media/media/catalog/product/** \
  > var/import/production-derived/ecom78-media/gcs-object-list-after.tsv
```

Do not commit object listings if they expose production-derived filenames or
customer-license paths.

## Staging Validation

Run validation only after the GCS copy is complete and Magento config points to
the target bucket/prefix through the `gcs-s3` adapter.

Required checks:

| Area | Probe | Evidence |
| --- | --- | --- |
| Product admin | Open representative simple and configurable products from the ECOM-7 slice. Confirm gallery thumbnails and base/small/thumbnail images render. | Product SKUs, admin result, missing paths if any. |
| Product storefront/API | Open representative product pages or GraphQL image fields and confirm media URLs return HTTP 200. | URLs or redacted path suffixes plus status codes. |
| Category admin/storefront | Open categories linked to retained products with category image or thumbnail values. | Category IDs and render result. |
| Customer-license media | If customer attachment media remains in scope, open retained customer attachment/license records and confirm file links render or download. | Customer IDs only; do not commit names, emails, or document filenames if sensitive. |
| GCS objects | Confirm required object paths exist under the configured staging prefix. | Object count summary and missing-path report. |

After validation, run targeted Magento cache clean only if stale generated image
metadata prevents rendering. Do not use global cache flush as the routine ECOM-78
validation step.

## Missing Source Path Rules

Create explicit follow-up tasks only when owner review requires cleanup for:

- referenced media paths missing from the approved source export;
- paths that exist in source media but fail GCS copy;
- paths copied to GCS but unreadable through Magento;
- customer-license media blocked by unresolved ECOM-55/ECOM-77 module decisions;
- stale DB media references that should be removed instead of copied.

Because staging is already deployed from ECOM-4, any required cleanup tasks
should block production cutover readiness rather than staging availability. For
the current ECOM-78 run, the source-missing product media paths are accepted and
no cleanup ticket is required.

## Completion Checklist

- Source media path export exists for the retained rehearsal dataset.
- Required source media binaries are copied where available; missing source
  paths are explicitly reported and owner-accepted.
- Copy procedure is executed against the staging GCS bucket/prefix.
- Product, category, and in-scope customer-license paths are validated through
  Magento/GCS.
- Follow-up cleanup tasks are created only for owner-required unresolved gaps.

Current result:

- Product media: copied where source objects exist and validated through GCS,
  direct UAT media URLs, representative GraphQL media URLs, and Admin Media
  Gallery sync.
- Category media: no category media rows returned for the retained product
  category set.
- Customer-license media: deferred to ECOM-77/ECOM-55 because the source extract
  lacks `customer_attachment`.
