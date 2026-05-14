# ECOM-60 Media Failure Runbook Draft

Status: Draft updated from ECOM-60 local validation

## Scope

This runbook covers Magento media storage failures for the selected remote media
adapter. It assumes web instances are stateless and media is not recovered from
local container disk.

## Signals

Watch for:

- admin upload failures;
- product import errors for missing or unreadable images;
- frontend 404/403 responses for `pub/media` paths;
- image resize failures;
- malformed Media Gallery thumbnail URLs such as `.thumbscatalog/...`;
- elevated PHP exceptions from `Magento_RemoteStorage`, `Magento_AwsS3`, AWS SDK,
  or the selected GCS module;
- object storage 401/403/404/429/5xx responses;
- CDN stale object reports after media replacement.

## Immediate Triage

1. Confirm whether the failure is write-only, read-only, delete-only, or CDN
   freshness.
2. Check Magento logs for the affected runtime role.
3. Confirm runtime secrets are present and current.
4. Check object storage IAM/HMAC/service account status.
5. List the expected object path in the bucket or fallback object store.
6. Test direct object storage access from the affected instance network.
7. Run `scripts/dev/ecom60-media-smoke` or the production equivalent against an
   isolated validation prefix.

## Write Failure Response

- Stop bulk imports or admin media changes if repeated failures are occurring.
- Preserve failed import files and Magento logs.
- Confirm bucket permissions allow create, update, metadata write, and delete
  for the runtime identity.
- Confirm the configured prefix is correct for the environment.
- Retry a single isolated write before resuming bulk work.

## Read Failure Response

- Confirm the object exists at the expected key.
- Confirm Magento generated the expected media URL.
- For Media Gallery thumbnails, prefer `.renditions/...` URLs. A
  `.thumbscatalog/...` request indicates the legacy CMS thumbnail URL path is
  being used and should be treated as an adapter/UI integration regression.
- Confirm the web instance can read through Magento's filesystem abstraction.
- Confirm CDN origin path and cache behavior.
- If only resized images fail, clear generated image cache paths for the
  affected product/media object and regenerate through Magento.

## ECOM-60 Validated Local Adapter

- Selected validation driver: `gcs-s3`.
- Module: `Vapewholesaleusa_GcsRemoteStorage`.
- Validation bucket: `vwu-magento-media`.
- Validation prefix: `ecom60`.
- Uniform bucket-level access remains enabled.
- Native `Magento_AwsS3` failed against this bucket because its write path emits
  object ACLs. GCS rejects those ACL requests with uniform bucket-level access.
- The local validation adapter writes through the AWS SDK/GCS XML API without
  object ACLs.
- Product image upload and frontend rendering passed.
- Category image selection from Media Gallery passed.
- Standalone Manage Gallery thumbnail rendering passed after switching the grid
  thumbnail URL to Media Gallery renditions.
- `scripts/dev/ecom60-media-smoke` now validates write/read/delete, copy,
  rename, directory marker creation, stream writes, stat, image metadata writes,
  and cleanup through Magento's media filesystem abstraction.
- Focused unit coverage can be run with
  `scripts/dev/test -c dev/tests/unit/phpunit.xml.dist --no-extensions app/code/Vapewholesaleusa/GcsRemoteStorage/Test/Unit`.

## Delete Failure Response

- Confirm whether the adapter intentionally leaves source objects in place.
- If deletion should remove the object, inspect adapter logs and permissions.
- Avoid manual bulk deletion until product media database references are
  understood.

## CDN Freshness Response

- Prefer versioned object paths when possible.
- For replacement-in-place, purge only affected media paths.
- Do not run global Magento cache flush or global CDN purge for routine media
  replacement unless the incident scope requires it.

## Rollback Or Fallback

If the selected adapter fails validation or has an incident that cannot be fixed
quickly:

1. Stop new media writes.
2. Preserve current bucket contents.
3. Switch Magento remote storage configuration to the last known-good adapter or
   S3-compatible fallback.
4. Sync media from the preserved source to the fallback target.
5. Validate read/write/delete on an isolated prefix.
6. Resume imports/admin writes after product and frontend media smoke tests pass.

## Open Items

- Decide whether `Vapewholesaleusa_GcsRemoteStorage` remains a validation
  prototype or becomes production code.
- Fill in production bucket, prefix, IAM, and CDN details.
- Fill in production log query examples after observability is wired.
- Fill in validated rollback commands after fallback target is selected.
