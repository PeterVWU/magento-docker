# ECOM-71 Product Import Media Validation

Status: Validated in staging on 2026-05-26

## Current Result

Owner validation confirmed product import media works through the
`Vapewholesaleusa_GcsRemoteStorage` `gcs-s3` adapter in staging.

Result summary:

- product import can create/update product media without relying on shared local
  disk;
- imported product images render in Admin and frontend;
- original and generated media paths resolve through the staging GCS-backed
  media path.

## Goal

Validate that Magento product import can create and update product media through
the `Vapewholesaleusa_GcsRemoteStorage` `gcs-s3` adapter without relying on a
shared local `pub/media` disk.

This is a staging validation follow-up to ECOM-60. It assumes the ECOM-78
staging layout:

- bucket: `vwu-magento-stg-media`;
- remote storage driver: `gcs-s3`;
- remote storage prefix: empty;
- effective object path for `catalog/product/a/b/example.jpg`:
  `gs://vwu-magento-stg-media/media/catalog/product/a/b/example.jpg`.

Do not commit CSVs, screenshots, object listings, or production-derived media
filenames if they expose sensitive catalog data.

## Preconditions

- Staging is deployed from an image that includes
  `Vapewholesaleusa_GcsRemoteStorage`.
- Web and worker containers write `remote_storage` config into `app/etc/env.php`
  from `MAGENTO_MEDIA_*` variables.
- The staging VM service account can read GCS HMAC credentials from Secret
  Manager.
- `bin/magento remote-storage:sync` and `bin/magento media-gallery:sync` can run
  from the worker/admin maintenance context.
- A small, non-customer-sensitive product set is available for import testing.

## Artifact Layout

Keep generated inputs and evidence in an ignored local or staging-only path:

```text
var/import/ecom71-product-media/
  ecom71-products.csv
  import-images/
    ecom71-base.jpg
    ecom71-gallery.jpg
  import-result.txt
  gcs-before.tsv
  gcs-after.tsv
  validation-notes.md
```

## CSV Shape

Use a minimal import CSV with one new disposable SKU and one existing staging
SKU. Include image fields that exercise base image and gallery behavior.

Required fields:

```text
sku,store_view_code,attribute_set_code,product_type,name,price,product_websites,visibility,status,qty,is_in_stock,image,small_image,thumbnail,media_gallery
```

Image field values should be relative filenames, for example:

```text
/ecom71-base.jpg,/ecom71-base.jpg,/ecom71-base.jpg,/ecom71-gallery.jpg
```

Stage the source files under Magento's expected import media directory:

```bash
mkdir -p var/import/ecom71-product-media/import-images
cp /secure/source/ecom71-base.jpg var/import/ecom71-product-media/import-images/
cp /secure/source/ecom71-gallery.jpg var/import/ecom71-product-media/import-images/
```

## Execution

Record the current object state for the test paths:

```bash
gsutil ls -l \
  gs://vwu-magento-stg-media/media/catalog/product/**/ecom71* \
  > var/import/ecom71-product-media/gcs-before.tsv
```

Run the import through Admin > System > Import:

- Entity Type: Products.
- Import Behavior: Add/Update.
- Validation Strategy: Stop on Error.
- Allowed Errors Count: `0`.
- Images File Directory: `var/import/ecom71-product-media/import-images`.
- Select File to Import: `var/import/ecom71-product-media/ecom71-products.csv`.

Record the validation and import history result in
`var/import/ecom71-product-media/import-result.txt`. If the deployed staging
image includes a site-specific CLI wrapper for product import, it may be used
instead, but record the exact command and options.

After import, refresh only the targeted caches/indexes needed for visibility:

```bash
bin/magento indexer:reindex catalog_product_attribute catalogsearch_fulltext
bin/magento cache:clean block_html full_page
bin/magento media-gallery:sync
```

Capture the new objects:

```bash
gsutil ls -l \
  gs://vwu-magento-stg-media/media/catalog/product/**/ecom71* \
  > var/import/ecom71-product-media/gcs-after.tsv
```

## Validation Matrix

| Area | Pass Condition | Evidence |
| --- | --- | --- |
| Import source handling | Magento reads images from the configured import image directory and completes without local shared-media assumptions. | Import result and import history. |
| Product media DB | `catalog_product_entity_media_gallery` and value rows exist for the imported SKU. | Redacted SQL count or admin product media state. |
| GCS object writes | Original product images exist under `media/catalog/product/...` in the staging bucket. | Object count and redacted path suffixes. |
| Generated media | Frontend/admin rendering creates any expected cache or rendition paths through GCS. | HTTP 200 media URLs and object path notes. |
| Admin rendering | Product edit page gallery thumbnails render. | SKU and admin result. |
| Frontend/API rendering | Product page or GraphQL image fields return media URLs that respond HTTP 200. | URL suffixes and status codes. |
| Missing file handling | A deliberately missing image produces a clear import error without partial hidden success. | Error message and failed row. |

## SQL Probes

Use redacted SKUs only:

```sql
SELECT e.sku, COUNT(*) AS gallery_rows
FROM catalog_product_entity e
JOIN catalog_product_entity_media_gallery_value_to_entity link
  ON link.entity_id = e.entity_id
WHERE e.sku IN ('ECOM71-NEW', 'ECOM71-UPDATE')
GROUP BY e.sku;
```

```sql
SELECT value
FROM catalog_product_entity_media_gallery
WHERE value LIKE '/e/c/ecom71%' OR value LIKE '%ecom71%';
```

## Failure Rules

Open a follow-up blocker if any of these occur:

- import only works when files are copied into shared `pub/media`;
- import succeeds but product media DB rows are missing;
- original files write to local disk but not GCS;
- generated frontend/admin image paths return 404 or 500 after targeted cache
  clean;
- missing import images are silently ignored for required image fields.

## Completion Checklist

- Minimal import CSV prepared and preserved in ignored artifacts.
- Source image files staged through the expected import media path.
- Import run completed for create and update behavior.
- Product media DB state verified.
- Original/cache/rendition objects verified in GCS.
- Product images render in admin and frontend/API.
- Missing-file behavior documented.
