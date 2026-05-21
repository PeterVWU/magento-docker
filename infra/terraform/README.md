# Magento OpenTofu Foundation

This directory contains the first OpenTofu infrastructure-as-code boundary for
the Magento infrastructure modernization. The configuration is
Terraform-compatible HCL, but OpenTofu is the
project CLI for formatting, initialization, and validation. It follows the
service contract in:

- `../../docs/ecom-6/managed-stateful-services.md`
- `../../docs/ecom-8/compute-runtime.md`
- `../../docs/ecom-4/staging-deployment.md`
- `../../docs/architecture/runtime-contract.md`

## Layout

```text
envs/
  stg/   # staging root module
  prod/  # production root module
modules/
  private-service-access/
  cloud-nat/
  cloud-sql-mysql/
  memorystore-redis/
  gcs-buckets/
  secret-manager/
  opensearch-gce/
  compute-runtime/
  external-https-lb/
```

The environment roots compose reusable modules and keep environment-specific
sizing, project, region, and network inputs out of the modules.

## Current Scope

This root now covers the Phase 5 stateful-services foundation, Phase 6 compute
runtime boundary, and Phase 7 external HTTPS load-balancer wiring.

Included in this slice:

- private services access for Cloud SQL and Memorystore dependencies;
- private Cloud NAT for package/API egress from private GCE nodes;
- Cloud SQL MySQL instance/database/users;
- Memorystore Redis instance;
- GCS media/assets buckets;
- Secret Manager secret containers and IAM hooks;
- self-managed OpenSearch-on-GCE scaffolding;
- SHA-tagged Magento web, worker, and release instance templates;
- a stateless web regional MIG with lightweight `/healthz` checks;
- a small worker MIG for cron and queue consumers;
- an optional global external HTTPS load balancer in front of the web MIG.

The load-balancer module can create a global frontend IP, backend service,
managed certificate, HTTP-to-HTTPS redirect, and optional Cloud DNS A record.
Cloud Armor and CDN are exposed as inputs but stay disabled by default for
staging.

## Compute Runtime

The `compute-runtime` module creates immutable instance templates around the
production container image from ECOM-19.

- `web` runs nginx/PHP-FPM only behind a regional MIG.
- `worker` runs cron and the configured queue consumers in a one-instance MIG by
  default.
- `release` has an instance template only. Operators or CI create a one-off VM
  from that template when deploy-time mutation is required.

Runtime startup installs Docker on a private Debian VM, pulls the SHA-tagged
runtime image, writes non-secret service wiring into `/etc/magento/runtime.env`,
fetches secret payloads from Secret Manager with the VM service account, and
starts the selected container role. The web health check uses `/healthz` on port
`8080`, matching the runtime image contract. Container health is role-aware in
the image: web checks the HTTP listener, while worker checks its cron/consumer
loop PIDs instead of probing the web port.

The templates emit environment, role, image SHA, and phase metadata for
observability and rollback. Rollback is a template/image rollback in the MIG,
with database rollback handled according to the runtime architecture contract.

## OpenSearch Bootstrap

The `opensearch-gce` module now installs and configures OpenSearch through the
instance startup script:

- installs OpenSearch from the official `3.x` APT repository;
- pins the package version with `opensearch_version`;
- sets `vm.max_map_count=262144`;
- mounts the attached persistent disk at `/var/lib/opensearch`;
- writes heap sizing under `/etc/opensearch/jvm.options.d/`;
- appends managed cluster/discovery settings to `/etc/opensearch/opensearch.yml`;
- installs the `repository-gcs` plugin;
- writes a helper to register the GCS snapshot repository.

The admin password is read from Secret Manager by the OpenSearch VM service
account at first boot. The module creates the secret container and IAM binding,
but it does not create the secret version. Operators must add the password value
before applying live infrastructure.

The package still starts from OpenSearch's packaged demo security material.
Before production launch, replace demo TLS/users with production certificates
and a reviewed security configuration. The OpenSearch node is intentionally
private-IP only; outbound package and Google API access goes through Cloud NAT,
not a public VM address.

## State And Secrets

Do not commit `.tfstate`, real `.tfvars`, generated plans, or provider
credentials. Use `terraform.tfvars.example` as the reviewable template and keep
real values in local/CI secret storage.

Backend state should use a GCS backend once the state bucket exists. Keep the
bootstrap step explicit because the state bucket may need to be created by a
separate foundation/admin process before these environment roots can use it.

Example backend config:

```bash
cp backend.gcs.example.hcl backend.stg.hcl
# edit bucket and prefix
tofu -chdir=envs/stg init -backend-config=../../backend.stg.hcl
```

Do not commit `backend.stg.hcl`, `backend.prod.hcl`, real `.tfvars`, state, or
plans.

## Validation

From a repo checkout with OpenTofu available as `tofu`:

```bash
tofu -chdir=infra/terraform fmt -recursive -check
tofu -chdir=infra/terraform/envs/stg init -backend=false
tofu -chdir=infra/terraform/envs/stg validate
tofu -chdir=infra/terraform/envs/prod init -backend=false
tofu -chdir=infra/terraform/envs/prod validate
```

The modules create secret containers and IAM bindings, not secret payloads.
Secret versions should be injected through a controlled operator or CI process.

## Applied Staging State

ECOM-6, ECOM-8, and ECOM-4 applied the staging root to GCP project `vwu-infra`.
The final OpenTofu drift check after the Phase 7 worker-health rollout reported
no changes.

Key staging outputs:

- Cloud SQL connection name: `vwu-infra:us-west1:magento-stg-mysql`
- Cloud SQL private IP: `172.24.1.3`
- Redis host/port: `172.24.0.4:6379`
- Media bucket: `vwu-magento-stg-media`
- Assets bucket: `vwu-magento-stg-assets`
- OpenSearch snapshot bucket: `vwu-magento-stg-opensearch-snapshots`
- OpenSearch node private IP: `10.138.0.5`
- Runtime service account: `magento-stg-runtime@vwu-infra.iam.gserviceaccount.com`
- Release service account: `magento-stg-release@vwu-infra.iam.gserviceaccount.com`
- OpenSearch service account: `magento-stg-opensearch@vwu-infra.iam.gserviceaccount.com`
- Web regional MIG: `magento-stg-web`
- Web health check: `magento-stg-web-healthz`
- Worker zonal MIG: `magento-stg-worker`
- Staging LB IP: `8.233.55.120`
- Staging LB hostname: `uat.vapewholesaleusa.com`
- Runtime image:
  `us-central1-docker.pkg.dev/vwu-infra/magento/magento-modern:ecom4-worker-health-20260521212833`

The OpenSearch admin secret has an initial staging version for bootstrap. Other
secret containers and Phase 6 runtime secrets intentionally do not have
committed or IaC-managed payloads. Populate them only through an explicit
operator/CI flow.
