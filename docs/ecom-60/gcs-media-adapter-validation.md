# ECOM-60 GCS Media Adapter Validation

Status: Local validation passed

## Goal

Validate the media storage implementation before ECOM-4 treats staging as
production-like and before ECOM-6 provisions managed media storage as a settled
dependency.

The target architecture remains the ECOM-54 runtime contract:

- Magento Open Source 2.4.8-p4.
- Stateless web instances on GCE Managed Instance Groups.
- No shared local media disk.
- No `gcsfuse` as the default path.
- Media writes and reads must work through Magento-supported filesystem paths.

## Provisional Candidate Order

### 1. Magento RemoteStorage + AwsS3 Driver Against GCS XML API

Validate this first.

Magento 2.4.8-p4 already includes:

- `Magento_RemoteStorage`
- `Magento_AwsS3`
- `Magento_AwsS3PageBuilder`
- `league/flysystem-aws-s3-v3`
- `aws/aws-sdk-php`

Adobe documents Remote Storage for media files and scheduled import/export
storage, with `aws-s3` as the supported built-in adapter. The installed
Magento code also exposes `--remote-storage-endpoint` and
`--remote-storage-path-style`, which lets the S3 client target a custom
endpoint.

Google Cloud Storage documents XML API interoperability with S3-compatible
tools and libraries by using:

- endpoint: `https://storage.googleapis.com`
- GCS HMAC access key
- GCS HMAC secret

This path keeps Magento on its native remote storage abstraction and avoids
introducing an old third-party Magento module unless GCS interoperability fails
under real Magento media behavior.

Validation risk:

- Adobe only lists Amazon S3 as the supported built-in storage adapter.
- GCS XML API interoperability is not the same as Adobe support for GCS through
  `Magento_AwsS3`.
- Bucket region, path-style URL generation, object metadata, cache headers, and
  delete semantics must be proven.
- Magento's installed `Magento_AwsS3` driver sends `ACL=private` for writes.
  GCS buckets with uniform bucket-level access reject object ACL operations.
- The generated public media URL must be compatible with the frontend/CDN path.

Initial validation finding:

- Bucket: `vwu-magento-media`.
- GCS HMAC credentials from the ignored workspace credential file worked for
  raw AWS SDK `putObject`, `getObject`, and `deleteObject` through
  `https://storage.googleapis.com` with path-style mode.
- The same raw AWS SDK write fails when `ACL=private` is sent:
  `InvalidArgument`.
- Magento `setup:config:set` also fails adapter validation because
  `Magento_AwsS3` writes its `storage.flag` object with `ACL=private`.
- The installed Magento setup option writes `path-style`, while
  `Magento_AwsS3Factory` reads `path_style`; local `env.php` had to be adjusted
  manually to force path-style mode for further testing.

Current conclusion:

Magento's native `Magento_AwsS3` adapter is not compatible with the current GCS
bucket settings when uniform bucket-level access is enabled, because Magento and
Flysystem emit object ACLs. To keep uniform bucket-level access, ECOM-60 added a
small local validation adapter, `Vapewholesaleusa_GcsRemoteStorage`, that uses
the same AWS SDK/GCS XML API path but writes objects without ACLs.

Custom adapter validation:

- Driver code: `gcs-s3`.
- Module: `Vapewholesaleusa_GcsRemoteStorage`.
- Bucket: `vwu-magento-media`.
- Prefix: `ecom60`.
- Result: `scripts/dev/ecom60-media-smoke` passed with
  `result=write-read-copy-rename-stream-stat-delete-ok`.
- Product image upload writes to GCS and renders in both admin and frontend.
- Category banner selection from Media Gallery works and uses
  `.renditions/...` media URLs.
- Standalone Manage Gallery renders uploaded asset thumbnails after overriding
  the listing thumbnail URL column to use Media Gallery renditions instead of
  the legacy CMS `.thumbs` URL path.
- The production-readiness smoke now covers directory marker creation,
  write/read/delete, copy, rename, stream writes through `openFile`, file stat,
  image metadata writes, and cleanup under an isolated smoke prefix.

The adapter is still a validation implementation. Before it becomes the selected
production path, it must pass product import media, deletion, multi-instance
reads, CDN delivery, and production observability tests.

Media Gallery thumbnail finding:

- Magento's Media Gallery listing column uses
  `Magento\MediaGalleryUi\Ui\Component\Listing\Columns\Url`.
- That class delegates thumbnails to `Magento\Cms\Model\Wysiwyg\Images\Storage`,
  which generated malformed URLs under this remote-storage path:
  `/media/.thumbscatalog/...`.
- The malformed URL reached `pub/get.php`, missed the object, and triggered
  Magento's placeholder-copy path with a null placeholder path, causing a 500.
- Category image insertion already used Media Gallery renditions successfully:
  `/media/.renditions/...`.
- The local validation module now overrides the Media Gallery listing thumbnail
  column for embedded and standalone listings so grid thumbnails use the same
  rendition path family.

### 2. AuroraExtensions GoogleCloudStorage

Validate only if the native RemoteStorage/AwsS3-to-GCS path fails.

Package: `auroraextensions/googlecloudstorage`

Current research:

- Magento 2 module for GCS media assets.
- MIT license.
- Latest Packagist release found: `1.2.1`, dated 2023-03-27.
- Requires PHP `^7.2||^8.0`, `magento/framework` `^100||^101||^102||^103`,
  `google/cloud-storage` `~1.14.0`.
- Uses a Google service account JSON key file path in `env.php`.
- Provides `gcs:media:sync`.

Validation risk:

- Small community footprint.
- Older dependency constraints.
- Service account JSON files are less desirable than Secret Manager-injected
  runtime configuration.
- It is not Magento's native `remote_storage` adapter path, so all admin,
  import, resize, cache, and multi-instance behavior needs direct proof.

### Rejected For Initial Validation

`bangerkuwranger/magento2-google-cloud-storage` is not an initial candidate.
The package release found is `1.0.1` from 2017, depends on a `dev-master`
wrapper, and the README describes limited support.

## Fallback Decision If GCS Fails

If no GCS-backed path passes validation, use an S3-compatible object storage
path that works with Magento's native `Magento_AwsS3` adapter rather than
forcing a fragile GCS-specific integration.

The fallback must still prove:

- admin media upload;
- product media upload;
- product import media;
- media deletion;
- image resize/cache generation;
- frontend/CDN media delivery;
- behavior across multiple stateless web instances.

## GCS Remote Storage Configuration Draft

Do not commit real credentials.

```bash
bin/magento config:set system/media_storage_configuration/media_database 0

bin/magento setup:config:set \
  --remote-storage-driver=gcs-s3 \
  --remote-storage-endpoint=https://storage.googleapis.com \
  --remote-storage-bucket="${ECOM60_GCS_BUCKET}" \
  --remote-storage-region="${ECOM60_GCS_REGION}" \
  --remote-storage-key="${ECOM60_GCS_HMAC_KEY}" \
  --remote-storage-secret="${ECOM60_GCS_HMAC_SECRET}" \
  --remote-storage-prefix="${ECOM60_GCS_PREFIX:-media}" \
  --remote-storage-path-style=1

bin/magento remote-storage:sync
bin/magento cache:clean config
```

Notes:

- `gcs-s3` is provided by `Vapewholesaleusa_GcsRemoteStorage`.
- Use a dedicated validation bucket or isolated prefix.
- Use HMAC credentials scoped to the validation bucket.
- `ECOM60_GCS_REGION` must match the value accepted by the AWS SDK signing path
  for the selected bucket and must be documented from the validation run.
- If public media delivery is through CDN, origin and cache behavior must be
  configured separately from Magento write validation.

## Validation Matrix

| Area | Required Result | Evidence |
| --- | --- | --- |
| Magento filesystem operations | Magento writes, reads, deletes, copies, renames, streams, stats files, and creates directories through `DirectoryList::MEDIA` with the configured remote driver. | `scripts/dev/ecom60-media-smoke` output and matching object observation. |
| Admin WYSIWYG/media upload | File created in GCS and visible from admin after cache clean. | Screenshot or command notes with object path. |
| Product image upload | Product image renders in admin and frontend. | Product image was uploaded, observed in `vwu-magento-media`, and confirmed visible on frontend product page. |
| Product import media | CSV import can consume staged import media and create product media records. | ECOM-71 staging procedure: [product-import-media-validation.md](../ecom-71/product-import-media-validation.md). |
| Media deletion | Admin/product media deletion removes or makes the object unreachable as expected. | ECOM-72 staging procedure: [media-deletion-validation.md](../ecom-72/media-deletion-validation.md). |
| Image resize/cache | Magento-generated resized media is written to remote media or generated deterministically. | Product frontend image renders; category Media Gallery insertion uses `.renditions/...`; Manage Gallery grid thumbnail renders via rendition URL override. |
| Stateless web instances | Instance B can read media uploaded by instance A without shared disk. | ECOM-73 staging procedure: [stateless-media-read-validation.md](../ecom-73/stateless-media-read-validation.md). |
| CDN/edge invalidation | Updated media can be purged or versioned without stale frontend delivery. | ECOM-74 staging procedure: [cdn-origin-observability-validation.md](../ecom-74/cdn-origin-observability-validation.md). |
| Failure handling | Read/write/auth/rate-limit failures have observable logs and an operator response. | Runbook section completed. |

## First Local Gate

After configuring remote storage on an installed local environment, run:

```bash
scripts/dev/ecom60-media-smoke
```

This checks Magento filesystem abstraction behavior for directory creation,
write/read/delete, copy, rename, stream writes, stat, and image metadata writes.
Passing it does not complete production readiness by itself; admin/product/import
and multi-instance behavior still need direct validation in staging.

For the lower-level S3 compatibility check, run:

```bash
scripts/dev/ecom60-gcs-s3-compat-smoke
```

Expected current result for `vwu-magento-media`:

- no-ACL AWS SDK write/read/delete: pass;
- `ACL=private` AWS SDK write: fail with `InvalidArgument`.

## Unit Coverage

Focused PHPUnit coverage for the custom module lives under:

```text
app/code/Vapewholesaleusa/GcsRemoteStorage/Test/Unit
```

Run it with:

```bash
scripts/dev/test -c dev/tests/unit/phpunit.xml.dist --no-extensions app/code/Vapewholesaleusa/GcsRemoteStorage/Test/Unit
```

The `--no-extensions` flag is currently required because Magento's bundled unit
test config bootstraps an Allure extension that does not implement the PHPUnit
10 extension interface used by this project.

Current coverage includes:

- no-ACL `putObject` writes;
- no-ACL `copyObject` copy/rename behavior, including encoded `CopySource`;
- stream write flush through `fileOpen`/`fileWrite`/`fileClose`;
- GCS `HeadObject` metadata/stat behavior;
- directory marker writes;
- CMS thumbnail path normalization and fallback media URL behavior;
- Media Gallery listing rendition thumbnail URL generation.

## Sources

- Adobe Experience League, Configure Remote Storage:
  https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/storage/remote-storage/remote-storage
- Google Cloud Storage, Interoperability with other storage providers:
  https://cloud.google.com/storage/docs/interoperability
- Google Cloud Storage, Uniform bucket-level access:
  https://cloud.google.com/storage/docs/uniform-bucket-level-access
- AuroraExtensions package:
  https://packagist.org/packages/auroraextensions/googlecloudstorage
- Bangerkuwranger package:
  https://packagist.org/packages/bangerkuwranger/magento2-google-cloud-storage
