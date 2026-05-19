# ECOM-6 OpenTofu Foundation

This directory contains the first OpenTofu infrastructure-as-code boundary for
Phase 5. The configuration is Terraform-compatible HCL, but OpenTofu is the
project CLI for formatting, initialization, and validation. It follows the
service contract in:

- `../../docs/ecom-6/managed-stateful-services.md`
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
```

The environment roots compose reusable modules and keep environment-specific
sizing, project, region, and network inputs out of the modules.

## Current Scope

This is the Phase 5 stateful-services foundation. It intentionally does not
create the future web MIG or load balancer. Those later resources will consume
these outputs and private-network contracts.

Included in this slice:

- private services access for Cloud SQL and Memorystore dependencies;
- private Cloud NAT for package/API egress from private GCE nodes;
- Cloud SQL MySQL instance/database/users;
- Memorystore Redis instance;
- GCS media/assets buckets;
- Secret Manager secret containers and IAM hooks;
- self-managed OpenSearch-on-GCE scaffolding.

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

ECOM-6 applied the staging root to GCP project `vwu-infra` and the final
OpenTofu drift check reported no changes.

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

The OpenSearch admin secret has an initial staging version for bootstrap. Other
secret containers intentionally do not have committed or IaC-managed payloads.
Populate them only through an explicit operator/CI flow.
