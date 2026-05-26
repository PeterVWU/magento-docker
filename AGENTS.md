# Agent Working Notes

This repository is the clean Magento Open Source modernization workspace. The
sibling legacy tree is reference-only; do not copy code, credentials, dumps, or
runtime artifacts from it into this repo.

## Current Infrastructure Direction

- Target runtime is GCE MIGs behind a load balancer, deployed from immutable
  Magento images.
- Stateful services are provisioned separately and consumed by later stateless
  web/worker/release runtimes.
- OpenTofu is the IaC CLI for `infra/terraform`; do not use Terraform commands
  unless the ticket explicitly changes the tool choice.
- GCP project for current staging work: `vwu-infra`.
- Staging Phase 5 stateful services are converged as of ECOM-6:
  - Cloud SQL MySQL `magento-stg-mysql`
  - Memorystore Redis `magento-stg-redis`
  - GCS buckets for media/assets/OpenSearch snapshots
  - Secret Manager containers
  - self-managed OpenSearch on private GCE
  - private services access and Cloud NAT
- Staging Phase 6 compute runtime is converged as of ECOM-8:
  - web regional MIG `magento-stg-web` with two healthy instances
  - worker zonal MIG `magento-stg-worker` with one running instance
  - web, worker, and release instance templates from a SHA-tagged Artifact
    Registry image
  - web `/healthz` backend check on port `8080`
  - runtime/release Secret Manager IAM and VM startup secret fetches
- ECOM-4 Phase 7 staging is converged:
  - external HTTPS LB, HTTP redirect, and managed certificate are applied for
    `uat.vapewholesaleusa.com`
  - UAT DNS is managed externally in Cloudflare and points to LB IP
    `8.233.55.120`
  - staging DB was reset and loaded with the first ECOM-7 rehearsal dataset
  - release role, reindex, GraphQL/catalog search, web health, and worker cron
    were validated against staging data
  - container health is role-aware: web probes `/healthz`, worker checks
    cron/consumer loop PIDs, and release checks the generated env file
- Cloud Armor and CDN/edge integration are still disabled by default and remain
  later hardening/performance work.

## OpenTofu Safety Rules

- Never commit real `terraform.tfvars`, backend configs, state files, generated
  plans, provider directories, or local credentials.
- Review `infra/terraform/README.md` before editing IaC.
- Prefer module changes over environment-root copy/paste when behavior should
  apply to both staging and production.
- Run formatting and validation before committing IaC changes:

```bash
tofu -chdir=infra/terraform fmt -recursive -check
tofu -chdir=infra/terraform/envs/stg validate
tofu -chdir=infra/terraform/envs/prod validate
```

If the local OpenTofu binary is used, it currently lives at
`$HOME/.local/bin/tofu`.

## Secrets And Data

- Do not print, commit, or paste secret payloads.
- Secret Manager containers may be managed by IaC, but secret versions should be
  injected through an explicit operator or CI process unless a ticket says
  otherwise.
- Raw production database dumps, media exports, `app/etc/env.php`, `.env`,
  Composer auth, and generated runtime files must stay out of Git.

## ECOM Follow-ups

- ECOM-79 owns production OpenSearch security hardening. The OpenSearch GCE
  module now expects managed PEM certificates and reviewed users from Secret
  Manager; see `docs/ecom-79/opensearch-security-hardening.md` before rolling
  OpenSearch nodes.
- The next migration rehearsal/export pass should repair the low
  `setup_module` count observed in the first rehearsal dataset so staging/prod
  imports do not depend on metadata repair steps.
- Put runtime image promotion into CI or a controlled release runbook before
  production cut-over. Staging currently uses secure tfvars for the active image
  tag and runtime sizing inputs.
