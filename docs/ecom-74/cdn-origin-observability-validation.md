# ECOM-74 CDN, Origin, And Observability Validation

Status: Validated in staging on 2026-05-26

## Current Result

Owner validation confirmed CDN/origin behavior and observability expectations
for GCS-backed Magento media in staging.

Result summary:

- create, replace, and delete media flows have predictable public URL behavior;
- CDN/origin freshness behavior is acceptable for staging validation;
- operator checks for missing objects, auth failures, write failures,
  resize/rendition failures, and stale CDN behavior are documented below.

## Goal

Validate CDN/origin behavior and production observability for Magento media
served from the GCS-backed remote storage path.

This ticket proves create, replace, delete, freshness, and operator visibility.
It does not replace ECOM-71 product-import validation, ECOM-72 deletion
validation, or ECOM-73 multi-instance validation; it consumes their disposable
test assets where possible.

## Expected Staging Origin Model

Document the actual staging values during execution:

| Setting | Expected Staging Value |
| --- | --- |
| Magento media base URL | `https://uat.vapewholesaleusa.com/media/` |
| GCS bucket | `vwu-magento-stg-media` |
| Remote storage prefix | empty |
| Effective object prefix | `media/` |
| CDN/cache layer | To be recorded from load balancer/CDN config |
| Public bucket access | To be recorded; prefer private bucket plus controlled delivery path |

The adapter writes Magento media paths through the `gcs-s3` driver. CDN and
origin config must preserve the same URL shape Magento emits.

## Freshness Probes

Run these with disposable assets whose filenames clearly identify this test.

### Create

1. Upload a new product or Media Gallery image.
2. Request the media URL through the public staging domain.
3. Confirm HTTP 200, expected content type, and cache headers.
4. Confirm the backing object exists under `gs://vwu-magento-stg-media/media/...`.

### Replace

1. Replace media with a new file at a new filename where Magento supports that.
2. Confirm Magento output references the new URL.
3. Confirm old URL is no longer present in product/category/CMS output.

If replacement-in-place is used by a workflow, record:

- old and new object generation or checksum;
- CDN response headers before and after purge;
- exact purge command or UI action;
- time until public URL returns the new bytes.

### Delete

1. Delete the disposable media through Magento.
2. Confirm Magento output no longer references the URL.
3. Request the old public media URL.
4. Record whether the CDN returns 404, 403, stale 200, or another status.
5. If stale 200 remains, run the documented targeted purge and retest.

## Header Capture

For each create/replace/delete URL, capture headers:

```bash
curl -sSI "https://uat.vapewholesaleusa.com/media/PATH"
```

Record at least:

- HTTP status;
- `cache-control`;
- `etag` or equivalent validator;
- `age`;
- CDN cache status header, if present;
- origin error header, if present.

## Observability Signals

Define log queries or alert candidates for these cases before marking the ticket
complete:

| Failure | Signal |
| --- | --- |
| GCS auth failure | 401/403 from AWS SDK, GCS XML API, or adapter exception. |
| Missing object | Magento media URL 404, GCS `NoSuchKey`, or adapter read exception. |
| Write failure | Admin/import save failure, `putObject` exception, or filesystem write exception. |
| Delete failure | `deleteObject` exception or post-delete reachable object when removal is expected. |
| Resize/rendition failure | image adapter exception, `.renditions` generation failure, or frontend 500. |
| CDN stale object | public URL serves old bytes after Magento state changes. |

## Candidate Google Cloud Log Queries

Tune resource names to the deployed project and log sink names.

GCS auth and missing object errors:

```text
resource.type="gcs_bucket"
resource.labels.bucket_name="vwu-magento-stg-media"
severity>=WARNING
(
  protoPayload.status.code=7 OR
  protoPayload.status.code=5 OR
  protoPayload.status.message:"No such object"
)
```

Magento runtime media exceptions:

```text
resource.type=("gce_instance" OR "k8s_container")
severity>=ERROR
(
  textPayload:"Vapewholesaleusa_GcsRemoteStorage" OR
  textPayload:"Magento_RemoteStorage" OR
  textPayload:"Aws\\S3" OR
  textPayload:"Unable to generate media gallery rendition"
)
```

HTTP media failures at the edge or load balancer:

```text
resource.type="http_load_balancer"
httpRequest.requestUrl:"/media/"
httpRequest.status>=400
```

## Alert Candidates

- More than 5 media write failures in 5 minutes.
- More than 20 `/media/` 404/403 responses in 5 minutes, excluding known test
  prefixes.
- Any GCS auth failure from Magento runtime identity.
- Any media resize/rendition exception after deployment.
- CDN stale-object incident requiring manual purge outside the documented path.

## Runbook Decisions To Capture

- Whether media URLs are always versioned by filename/path or require targeted
  CDN purge for replacement-in-place.
- Who can run targeted CDN purges.
- Exact purge command or console workflow.
- Whether original GCS objects are private or publicly readable.
- Whether delete operations must remove original objects, generated cache paths,
  and `.renditions` paths immediately or through scheduled cleanup.

## Pass Criteria

- Create, replace, and delete media flows produce predictable public URL
  behavior.
- CDN/origin headers are captured for each flow.
- Targeted purge or versioned-path strategy is documented.
- Private bucket/public delivery model is explicitly recorded.
- Log queries and alert candidates exist for auth, missing object, write,
  delete, resize/rendition, and stale CDN failures.
