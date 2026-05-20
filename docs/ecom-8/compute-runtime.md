# ECOM-8 Compute Runtime

## Scope

Phase 6 adds the GCE compute runtime for Magento web, worker, and release roles.
It consumes the Phase 5 managed service outputs and the Phase 3 immutable
runtime image contract.

This phase creates:

- a reusable `compute-runtime` OpenTofu module;
- SHA/image-versioned web, worker, and release instance templates;
- a regional stateless web Managed Instance Group;
- a small worker Managed Instance Group;
- a lightweight `/healthz` HTTP health check on port `8080`;
- firewall rules for allowed web traffic sources;
- runtime metadata for environment, role, image SHA, and phase.

The external HTTPS load balancer, DNS, certificate, CDN, and Cloud Armor
boundary is intentionally left for the staging deployment phase.

## Runtime Roles

### Web

The web MIG runs the `web` container role. Startup only pulls the selected image,
fetches runtime secrets, and starts nginx/PHP-FPM through the production
entrypoint. Web nodes do not run cron, consumers, `setup:upgrade`, static deploy,
DI compile, or cache flushes.

### Worker

The worker MIG runs the `worker` container role. The initial footprint is a
single VM by default, with cron enabled and queue consumers supplied through
`worker_consumers`.

Consumer runs are bounded with `worker_consumer_max_messages` so process restart
and image rollout remain predictable.

### Release

The release role is represented as an instance template, not a standing MIG.
Deploy automation should create a one-off VM from the release template when
database mutation is needed, wait for completion, and then delete the VM. The
release template uses the release service account and release database user.

## Secret Wiring

The environment roots automatically grant:

- the runtime service account access to runtime secrets;
- the release service account access to release and runtime secrets needed for
  release commands.

The startup script reads the latest Secret Manager versions at boot and writes
them to the container env file on the VM. Secret payloads remain out of
Terraform state because the root modules manage only secret containers and IAM,
not secret versions.

## Rollout And Rollback

Each image promotion should update `runtime_image` and `runtime_image_sha`,
which creates new instance templates. The web MIG uses proactive replacement
with `max_unavailable_fixed = 0` and `max_surge_fixed = 3`; regional MIGs need
enough fixed surge capacity for the zones in use.

Rollback means selecting the previous known-good image/template for the MIG.
Schema/data rollback follows the database rollback guidance in the architecture
contract.

## Required Inputs

Set these per environment in local or CI-provided tfvars:

```hcl
runtime_image     = "us-central1-docker.pkg.dev/PROJECT/REPOSITORY/magento-modern:COMMIT_SHA"
runtime_image_sha = "COMMIT_SHA"

web_target_size    = 2
worker_target_size = 1
worker_consumers   = ["async.operations.all"]
```

The web runtime source ranges/tags should be restricted to the eventual load
balancer backend path:

```hcl
web_runtime_source_ranges = ["130.211.0.0/22", "35.191.0.0/16"]
web_runtime_source_tags   = []
```

## Staging Result

The staging compute runtime was applied in `vwu-infra` with image:

```text
us-central1-docker.pkg.dev/vwu-infra/magento/magento-modern:be9cadb291f7a5ac6946876ab2bed23a3d592ef3
```

Final verification reported no OpenTofu changes, two healthy web instances in
the regional MIG, and one running worker instance in the zonal MIG. The staging
runtime secret containers now have operator-injected versions, but the payloads
remain outside Git and outside Terraform state.
