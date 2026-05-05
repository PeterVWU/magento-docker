# Magento Runtime Architecture Contract

Status: Draft for ECOM-54

## Decision

The new Magento backend runs as a headless commerce engine on GCE Managed
Instance Groups using immutable container images.

GKE and Cloud Run are out of scope for this phase.

## Runtime Roles

### Web

The `web` role handles HTTP traffic with nginx and PHP-FPM.

Web instances are stateless. They do not run cron, queue consumers, database
migrations, dependency injection compilation, static content deployment, or
global cache flushes at startup.

### Worker

The `worker` role runs Magento cron and queue consumers.

Worker runtime is separate from autoscaling web instances so background work is
not duplicated during web scale-out or rolling deploys.

### Release

The `release` role is a one-off deploy job, VM, or container invocation using
the target production image.

The release role owns deploy-time mutation work such as `setup:upgrade`,
targeted cache clean, and release sanity checks. Web and worker instances must
not run this work opportunistically at startup.

## Stateful Services

Stateful services are managed outside the web and worker containers:

- Cloud SQL for MySQL.
- Memorystore Redis for Magento cache, page cache, sessions, and locks.
- Managed OpenSearch for catalog search.
- GCS for media storage.
- Secret Manager for runtime secrets.

Magento Open Source is the target edition. Adobe Commerce Cloud-specific
features and ece-tools behavior are not architecture assumptions.

Redis usage must be separated by logical DBs or key prefixes for cache, full
page cache, sessions, and locks. Cache operations must not accidentally clear
session or lock state.

## Search

Use managed OpenSearch, not Elasticsearch.

For Magento Open Source 2.4.8-p4, target the OpenSearch version listed in the
official Magento system requirements for that patch level. Elasticsearch is not
the default because Magento 2.4.8 marks Elasticsearch options as deprecated.

Search should use private network access where the provider supports it. Initial
sizing should be conservative and resized from catalog size, reindex duration,
query latency, CPU, memory, and disk metrics.

## Media

Magento media storage uses a GCS-backed media module.

`gcsfuse` is not the default architecture. Local disk plus sync is also not the
default path. Any selected GCS module must be tested with admin uploads, product
media, imports, cache invalidation, and frontend media delivery before staging is
treated as production-like.

Magento native remote storage is S3-focused, so GCS support must be provided by
a compatible third-party or custom adapter. If no GCS adapter passes validation,
the fallback decision is to use an S3-compatible object storage path rather than
forcing a fragile GCS integration.

## Cloud SQL Connection

MIG instances connect to Cloud SQL through private IP direct connectivity.

Cloud SQL Auth Proxy or connector-based access is not the default for this phase.
Database credentials come from Secret Manager.

## Build-Time Work

The production image is built in CI/CD from a clean checkout.

The image build is responsible for work that can be made immutable:

```bash
composer install --no-dev --prefer-dist
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

The runtime image must already contain generated code and deployed static/admin
assets required to start quickly.

## Deploy-Time Migration Work

Database mutations happen in a controlled one-off deploy step, not in normal
container startup.

`setup:upgrade` must be run by the `release` role only.

Expected migration pattern:

```bash
bin/magento maintenance:enable
bin/magento setup:upgrade --keep-generated
bin/magento cache:clean config layout block_html full_page
bin/magento maintenance:disable
```

Maintenance mode usage depends on the migration risk. Low-risk changes may use a
narrower rollout procedure, but no web or worker instance may run
`setup:upgrade` opportunistically at startup.

## Startup Rules

Container startup may load config/secrets and start services only.

Startup must not run:

- `setup:di:compile`
- `setup:static-content:deploy`
- `setup:upgrade`
- global `cache:flush`
- global cache purge
- cron bootstrap work on web nodes
- queue consumers on web nodes

This keeps autoscaling and rolling deployments predictable.

## Health Checks

Load balancer and MIG health checks must be lightweight.

Use `/healthz` or `/health_check.php` to verify nginx/PHP-FPM readiness and basic
application bootstrap only. Health checks must not depend on expensive Magento
page rendering, search queries, checkout flows, or third-party integrations.

Deeper smoke tests run in CI/CD and release validation, not as load balancer
health checks.

## Cache Rules

Routine deploys should use targeted cache clean and targeted edge/CDN purge.

Global `bin/magento cache:flush` is reserved for manual or emergency operation
because it can clear shared Redis state and trigger traffic spikes.

## Deploy Model

Images are tagged by commit SHA.

Each deploy creates a new instance template referencing the target image tag.
Web MIG rollout uses health checks and rolling updates.

Rollback means reverting the MIG to the previous known-good instance template and
image tag.

Worker rollout must stop old consumers cleanly before starting the new worker
image.

## Observability Contract

Observability starts with the runtime contract, even though dashboards and alerts
are implemented later.

Logs and metrics must distinguish:

- environment;
- runtime role: `web`, `worker`, or `release`;
- image commit SHA;
- instance/template version where available;
- request or correlation ID for HTTP/API flows where possible.

Container logs should go to stdout/stderr or a documented log path collected by
the instance logging agent. Magento, nginx, PHP-FPM, cron, consumer, and release
logs must be attributable to the correct role.

## Worker Sizing

Start with a small, explicit worker footprint:

- one dedicated worker VM or one-instance worker MIG;
- cron separated from long-running queue consumers;
- supervised consumer processes with an explicit consumer list;
- bounded consumer runs where appropriate, such as max messages;
- scale only after measuring queue depth, cron duration, failures, and CPU/memory.

## Database Rollback

Database migrations are treated as mostly forward-only.

Before running `setup:upgrade` against staging or production, take a Cloud SQL
backup or snapshot.

Normal application rollback reverts the MIG to the prior image/template. If a
schema or data migration fails, prefer a forward-fix. Restore from backup only
when data corruption or an unrecoverable migration requires it.

High-risk custom migrations must include either an explicit rollback script or a
documented forward-fix plan before release.

## Frontend Boundary

Magento owns backend commerce behavior, admin workflows, product/catalog data,
customer/account data, checkout/payment/order behavior, and APIs.

A separate frontend owns storefront UX. Legacy Magento theme code from `vusa244`
is reference material only unless explicitly retained for admin, email, PDF, or
backend API behavior.

## Validation Items

- OpenSearch provider and exact version: confirm compatibility with Magento Open
  Source 2.4.8-p4 and expected catalog/search volume.
- GCS media module selection: confirm module/package, support status, and test
  plan for admin/import/media delivery behavior.
- Worker process counts: define exact cron schedule, queue consumer list, and
  initial process counts.
