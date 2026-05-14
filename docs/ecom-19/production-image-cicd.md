# ECOM-19 Production Image and CI/CD Foundation

## Scope

Phase 3 builds the immutable Magento runtime foundation for GCE Managed Instance
Groups. The architecture contract remains the source of truth:

- [Runtime architecture contract](../architecture/runtime-contract.md)

This phase does not provision Cloud SQL, Redis, OpenSearch, GCS media, instance
templates, or MIGs. It prepares the image, CI checks, and deploy contract those
later phases will consume.

## Image Contract

The root `Dockerfile` is the production image. It uses BuildKit and multi-stage
builds:

1. PHP extension base with Composer.
2. Build stage with `composer install --no-dev`, DI compile, and static asset
   deployment for the configured locale.
3. Runtime stage with nginx, PHP-FPM, generated code, static assets, and the
   role entrypoint.

The static asset step uses a temporary build-only MariaDB 11.4 process and the
minimal store/config/theme/translation tables Magento needs to resolve admin
and storefront assets. `app/etc/env.php` is removed before the runtime stage,
so production connections still come from the deployment environment and secret
wiring.

Composer credentials must be passed as a BuildKit secret named
`composer_auth`. The secret value is the JSON content normally stored in
`auth.json`.

```bash
DOCKER_BUILDKIT=1 docker build \
  --secret id=composer_auth,src=../composer-auth.json \
  --build-arg VCS_REF="$(git rev-parse HEAD)" \
  --build-arg BUILD_DATE="$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
  -t magento-modern:"$(git rev-parse --short HEAD)" .
```

The runtime image exposes port `8080` and has a lightweight `/healthz` endpoint
for load balancer and MIG health checks.

## Runtime Roles

Select a role with `MAGENTO_RUNTIME_ROLE` or the first container argument.

```bash
docker run --rm magento-modern:sha web
docker run --rm -e MAGENTO_CONSUMERS=async.operations.all magento-modern:sha worker
docker run --rm magento-modern:sha release php bin/magento list
```

`web` starts nginx and PHP-FPM only. It must not run cron, consumers,
`setup:upgrade`, DI compile, static deploy, global cache flush, or global cache
purge.

`worker` runs Magento cron when `MAGENTO_RUN_CRON=1` and starts comma-separated
queue consumers from `MAGENTO_CONSUMERS`. Consumer runs are bounded by
`MAGENTO_CONSUMER_MAX_MESSAGES` and restarted by the entrypoint.

`release` is the only role allowed to run deploy-time mutation. To run the
default release sequence, set `MAGENTO_RELEASE_RUN_UPGRADE=1`. Without that
explicit flag or an explicit command, the release role exits.

Default release sequence:

```bash
bin/magento maintenance:enable
bin/magento setup:upgrade --keep-generated
bin/magento cache:clean config layout block_html full_page
bin/magento maintenance:disable
```

## CI Foundation

`.github/workflows/production-image.yml` runs:

- committed secret scan;
- Docker/context guardrail checks;
- Docker Buildx production image build using the `COMPOSER_AUTH_JSON` secret;
- smoke tests for `bin/magento` through web, worker, and release command paths;
- image history checks for forbidden startup mutation commands;
- SHA-tagged push to Artifact Registry on `main` when GCP/GAR secrets are set.

Required GitHub secrets:

- `COMPOSER_AUTH_JSON`
- `GCP_WORKLOAD_IDENTITY_PROVIDER`
- `GCP_SERVICE_ACCOUNT`
- `GAR_HOSTNAME`
- `GAR_PROJECT`
- `GAR_REPOSITORY`

## Deploy Design

Later deployment jobs should promote the exact SHA-tagged image built by CI.
Each staging or production deploy creates a new GCE instance template that
references that image tag. Web MIG rollout uses health checks and a rolling
update. Rollback points the MIG back to the previous known-good instance
template and image tag.

Worker rollout must stop old consumers cleanly before starting the replacement
worker image. The release role must run as one controlled invocation before web
or worker rollout proceeds when `setup:upgrade` is required.
