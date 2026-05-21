# ECOM-4 Staging Deployment

## Scope

Phase 7 turns the staging compute runtime into a production-like staging
environment. Phases 5 and 6 already provide the private stateful services,
runtime service accounts, web MIG, worker MIG, release template, and startup
secret wiring. This phase adds the external HTTPS entry point and records the
remaining validation path for Magento behavior.

This phase creates or wires:

- global external HTTPS load balancer;
- global static frontend IP;
- backend service pointed at the Phase 6 web regional MIG;
- `/healthz` backend health checks through the existing compute runtime health
  check;
- optional Google-managed SSL certificate;
- optional HTTP-to-HTTPS redirect;
- optional Cloud DNS A record;
- outputs for the staging URL path and load-balancer resources.

The load balancer uses the named `http` port exposed by the web MIG and does not
change web, worker, or release startup behavior.

## Terraform Inputs

Enable the load balancer with real staging hostnames:

```hcl
enable_external_https_lb = true
external_https_lb_domains = [
  "uat.vapewholesaleusa.com",
]
```

If the staging DNS zone is managed in the same GCP project, also set:

```hcl
external_https_lb_dns_managed_zone = "YOUR-CLOUD-DNS-ZONE"
external_https_lb_dns_record_name  = "uat.vapewholesaleusa.com."
```

If DNS is managed outside this root, leave those values null and create the A
record from `external_https_lb_ip_address`.

The default staging edge posture is intentionally minimal:

```hcl
external_https_lb_enable_http_redirect = true
external_https_lb_enable_cdn           = false
external_https_lb_security_policy      = null
```

Cloud Armor and CDN can be enabled in later edge phases without replacing the
web MIG.

## Apply Order

1. Confirm Phase 6 web instances are healthy in `magento-stg-web`.
2. Set `enable_external_https_lb`, certificate domains, and DNS values in secure
   tfvars or CI variables.
3. Run OpenTofu plan for staging.
4. Apply the load-balancer resources.
5. Wait for the managed certificate to become active after DNS points at the
   frontend IP.
6. Validate `https://STAGING_HOST/healthz` through the load balancer.
7. Run release-role deploy mutation and Magento sanity checks.

## Validation Checklist

Infrastructure:

- OpenTofu plan converges with no unrelated stateful-service changes.
- Backend service points at the Phase 6 web regional MIG.
- Load-balancer health check remains the lightweight `/healthz` endpoint.
- Managed certificate is active for the staging hostname.
- HTTP redirects to HTTPS when redirect is enabled.

Runtime:

- Web startup remains stateless and does not run `setup:upgrade`, DI compile,
  static deploy, cron, consumers, or global cache flush.
- Worker role remains separate from web autoscaling and uses the worker-specific
  container healthcheck instead of the web `/healthz` probe.
- Release role runs deploy mutation once and leaves auditable logs.
- Magento can read/write media through the GCS media adapter.
- OpenSearch indexing/search works against staging data.
- Redis-backed cache/session/lock paths are functional.
- GraphQL/API smoke checks pass against the staging hostname.

## Current Status

The reusable external HTTPS load-balancer module is wired into staging and prod
roots behind `enable_external_https_lb`. The staging load balancer, HTTP
redirect, and managed certificate are applied for `uat.vapewholesaleusa.com`
with DNS managed externally in Cloudflare.

Current frontend IP:

```text
8.233.55.120
```

The Cloudflare DNS-only `A` record for `uat.vapewholesaleusa.com` points to that
IP. The UAT hostname returns HTTP 200 over HTTPS.

The first ECOM-7 rehearsal dataset has been imported into staging, the release
role has run successfully, and reindex/search/GraphQL/web/worker checks have
passed. The imported dataset still needs a follow-up migration-export cleanup
for the low `setup_module` metadata count observed during import.
