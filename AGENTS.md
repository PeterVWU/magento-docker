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
`.tools/opentofu/tofu` and is ignored by Git.

## Secrets And Data

- Do not print, commit, or paste secret payloads.
- Secret Manager containers may be managed by IaC, but secret versions should be
  injected through an explicit operator or CI process unless a ticket says
  otherwise.
- Raw production database dumps, media exports, `app/etc/env.php`, `.env`,
  Composer auth, and generated runtime files must stay out of Git.

## ECOM-6 Follow-ups

- ECOM-79 owns production OpenSearch security hardening. ECOM-6 staging uses the
  packaged OpenSearch security bootstrap only as an initial scaffold.
- Later MIG/load-balancer tickets should consume the Phase 5 outputs rather than
  recreating stateful resources.
