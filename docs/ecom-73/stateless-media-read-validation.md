# ECOM-73 Stateless Media Read Validation

Status: Validated in staging on 2026-05-26

## Current Result

Owner validation confirmed staging media reads remain stateless across web
instances with the `Vapewholesaleusa_GcsRemoteStorage` `gcs-s3` adapter.

Result summary:

- media uploaded from one staging instance can be read/rendered from another;
- no validation workflow depends on a shared local `pub/media` disk;
- generated media/cache behavior remains compatible with stateless web
  instances.

## Goal

Validate that staging web instances remain stateless for Magento media reads
when using the `Vapewholesaleusa_GcsRemoteStorage` `gcs-s3` adapter.

Instance B must be able to read/render media uploaded from instance A without a
shared local `pub/media` disk. Local generated media/cache may exist as
ephemeral acceleration only; it must not be the source of truth.

## Preconditions

- Staging MIG or equivalent has at least two web instances.
- The load balancer can be bypassed or requests can be pinned to a specific
  instance for validation.
- Both instances have the same `MAGENTO_MEDIA_*` runtime configuration and GCS
  HMAC Secret Manager access.
- Disposable product and Media Gallery test assets are available.

## Instance Inventory

Record instance identity and media config from each node:

```bash
hostname
php -r '$env = include "app/etc/env.php"; var_export($env["remote_storage"] ?? []);'
```

Expected staging config:

```text
driver: gcs-s3
bucket: vwu-magento-stg-media
prefix: empty
endpoint: https://storage.googleapis.com
path-style: 1
```

## Upload On Instance A

Pin Admin or maintenance traffic to instance A, then upload:

- one product image;
- one category or Media Gallery image.

Capture:

- SKU or category ID;
- generated media URL suffix;
- GCS object path;
- instance A hostname;
- relevant Magento log lines.

## Read On Instance B

Pin frontend/admin traffic to instance B and verify:

- product edit page gallery thumbnail renders;
- product frontend or GraphQL media URL returns HTTP 200;
- Media Gallery thumbnail/rendition renders;
- category/CMS media renders if included.

Use the URL suffixes produced by instance A. Do not copy files between instance
local disks.

## Local Disk Eviction Probe

On instance B only, clear generated local media/cache paths for the disposable
asset. Keep this targeted to the test paths:

```bash
find pub/media/catalog/product/cache -path '*ecom73*' -type f -print
find pub/media/.renditions -path '*ecom73*' -type f -print
```

After confirming the matched paths are only disposable test assets, remove those
specific generated files from instance B and request the same frontend/admin
URLs again.

Pass condition: Magento re-reads source media through GCS or regenerates the
derived asset without relying on another instance's local disk.

## Race And Rendition Checks

Exercise concurrent reads after clearing instance-local generated files:

```bash
for i in 1 2 3 4 5; do
  curl -sSI "https://uat.vapewholesaleusa.com/media/PATH-FROM-INSTANCE-A" &
done
wait
```

Review logs for duplicate generation warnings, lock contention, 404s, 500s, or
GCS throttling/auth errors.

## Validation Matrix

| Area | Pass Condition | Evidence |
| --- | --- | --- |
| Config parity | Both instances use the same `gcs-s3` remote storage settings. | Redacted `env.php` remote storage output. |
| Cross-instance original read | Instance B reads source media uploaded on instance A. | URL suffix and HTTP 200 from instance B. |
| Admin thumbnails | Instance B renders Admin product and Media Gallery thumbnails. | Admin result and log notes. |
| Generated cache rebuild | Clearing instance B local generated files does not break rendering. | Before/after HTTP status and object path notes. |
| No shared disk dependency | No manual copy to instance B `pub/media` is required. | Validation notes. |
| Race behavior | Concurrent first reads do not produce user-visible failures. | curl statuses and log notes. |

## Failure Rules

Open a follow-up blocker if:

- instance B can render only after files are copied from instance A;
- generated image rebuild writes only to local disk and fails after local
  eviction;
- Media Gallery thumbnails regress to malformed `.thumbscatalog/...` URLs;
- concurrent first reads produce 404/500 responses or corrupt generated images;
- any instance lacks GCS credential access or has divergent remote storage
  prefix settings.
