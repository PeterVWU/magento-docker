# ECOM-6 Managed Stateful Services Contract

Status: Draft implementation contract

## Goal

Define the managed stateful services that staging and production will consume
before infrastructure-as-code is introduced.

This phase turns the runtime contract into provisionable service decisions for:

- Cloud SQL for MySQL.
- Memorystore for Redis.
- OpenSearch.
- GCS media/static buckets.
- Secret Manager.

The source architecture remains:

- [Runtime architecture contract](../architecture/runtime-contract.md)
- [ECOM-60 GCS media adapter validation](../ecom-60/gcs-media-adapter-validation.md)

## Environment Boundary

Staging and production must be separate at the service layer. The default model
is one GCP project per environment when the landing zone permits it:

| Environment | Purpose | Isolation expectation |
| --- | --- | --- |
| `stg` | production-like validation, migration rehearsal, release verification | separate databases, Redis, buckets, secrets, OpenSearch service/users |
| `prod` | customer-facing runtime | no shared mutable state with staging |

If the first IaC slice must temporarily share a project, every mutable resource
still needs an explicit environment prefix and separate IAM bindings. Shared
service instances across staging and production are out of scope.

## Relationship To Future MIG And Load Balancer Work

Phase 5 defines the shared state that later stateless runtimes will consume.
The later compute/runtime phase is expected to place `web` instances in a GCE
Managed Instance Group behind a load balancer. Those instances should be
replaceable at any time without losing application state.

That means:

- the load balancer and backend-service wiring belong to the later MIG phase;
- Cloud SQL, Redis, OpenSearch, GCS, and Secret Manager must all be reachable
  from future MIG instances over private networking where applicable;
- Private GCE services that install packages at boot, including self-managed
  OpenSearch, require controlled outbound egress through Cloud NAT rather than
  public VM IPs;
- web instances must not depend on local database files, local media, or local
  search indexes;
- shared service naming, IAM, and network boundaries in this document should be
  treated as inputs to the later instance-template and MIG work.

## Naming Contract

Use one short environment token and one service token consistently:

| Resource family | Staging pattern | Production pattern |
| --- | --- | --- |
| Cloud SQL | `magento-stg-mysql` | `magento-prod-mysql` |
| Redis | `magento-stg-redis` | `magento-prod-redis` |
| OpenSearch cluster | `magento-stg-search` | `magento-prod-search` |
| Media bucket | `vwu-magento-stg-media` | `vwu-magento-prod-media` |
| Static/import bucket | `vwu-magento-stg-assets` | `vwu-magento-prod-assets` |
| Secret prefix | `magento/stg/...` | `magento/prod/...` |

Region labels, numeric suffixes, or provider-imposed naming adaptations may be
added by IaC, but the human-readable stem should remain stable.

## Service Decisions

### Cloud SQL for MySQL

| Decision | Staging | Production |
| --- | --- | --- |
| Engine | MySQL `8.4` | MySQL `8.4` |
| Connectivity | private IP only | private IP only |
| Topology | zonal initially | regional HA |
| Backups | automated backups + PITR | automated backups + PITR |
| Maintenance | explicit window | explicit window |
| Application users | least-privilege runtime + release/migration user | least-privilege runtime + release/migration user |

Cloud SQL must use private IP direct connectivity from MIG instances. The VPC
therefore needs private services access before the database can be provisioned.
Application runtime must not use a human-admin database user.

Recommended user split:

| User class | Purpose |
| --- | --- |
| `magento_app` | normal web/worker traffic; CRUD only on Magento schema |
| `magento_release` | one-off release role; schema migration privileges needed by `setup:upgrade` |
| break-glass admin | operator-only; not mounted into normal runtime |

Backups and point-in-time recovery are part of the deployment contract, not an
afterthought. Before each risky `setup:upgrade`, the release process should
record the latest successful backup/PITR window and the restore target plan.

### Memorystore for Redis

Use Memorystore for Redis with a single environment-specific endpoint, but do
**not** rely on multiple logical Redis databases for blast-radius separation.
The portable contract is key-prefix separation:

| Magento use | Prefix |
| --- | --- |
| default cache | `cache:` |
| full page cache | `fpc:` |
| sessions | `session:` |
| locks | `lock:` |

Why prefixes instead of logical DB numbers:

- Magento needs cache operations that are safe for shared Redis use.
- Prefixes remain visible and auditable.
- They avoid coupling the architecture to `SELECT` behavior or provider-specific
  database semantics.

Routine deploys may clean `cache:` and `fpc:` keys only. Session and lock keys
must not be cleared by routine cache maintenance.

Recommended initial shape:

| Environment | Tier | Notes |
| --- | --- | --- |
| `stg` | Standard Tier, smallest practical HA size | keep behavior production-like |
| `prod` | Standard Tier HA | start conservatively, resize from memory/eviction/latency metrics |

Enable Redis authentication and in-transit encryption if the Magento PHP Redis
client path is validated with TLS in the target image. If TLS adds a client
compatibility wrinkle during the first staging cut, document that explicitly
rather than silently disabling encryption.

### Self-Managed OpenSearch On GCP

#### Topology choice

Selected direction for this phase: **self-managed OpenSearch on GCE**.

Reasoning:

- Magento Open Source `2.4.8-p4` expects OpenSearch `3`.
- Google Cloud does not provide a first-party managed OpenSearch service.
- Keeping search inside GCP avoids adding a second vendor/control plane to the
  platform.
- The team accepts the operational responsibility for OpenSearch in exchange
  for a GCP-only footprint.

OpenSearch must remain environment-separated:

| Environment | Cluster | Index prefix |
| --- | --- | --- |
| `stg` | `magento-stg-search` | `magento_stg_` |
| `prod` | `magento-prod-search` | `magento_prod_` |

Use dedicated service users rather than the cluster admin account:

| User class | Expected permission |
| --- | --- |
| `magento_app` | read/write only for Magento-owned indices |
| operator/admin | dashboard and cluster administration |

#### Initial sizing and monitoring

Start staging small enough to be economical but production-like enough to
exercise reindex and failure behavior. Production should start as an HA cluster,
not a single-node dev cluster.

The first IaC slice should expose these as variables rather than hard-coding
them:

- machine type and node count;
- dedicated master/data topology if split;
- storage size;
- subnet and firewall tags/service accounts;
- OpenSearch major version;
- index prefix.

Track at least:

- cluster health;
- JVM heap pressure / memory pressure;
- CPU;
- free storage;
- search latency;
- indexing latency / failures;
- rejected thread-pool operations;
- Magento full reindex duration.

Release validation should include a full catalog reindex and a small storefront
search smoke test before staging is considered production-like.

#### Operational baseline

Self-managed means Phase 5 must also define day-one operator duties:

- OS patching and OpenSearch version upgrades;
- secure bootstrap of cluster credentials;
- disk growth and shard allocation response;
- snapshot policy and restore testing;
- JVM heap / GC tuning;
- node replacement and rolling-restart procedure;
- alerting for cluster health, unassigned shards, high heap, disk watermarks,
  rejected requests, and failed snapshots.

Snapshots should be written to an environment-specific GCS snapshot bucket or
prefix. OpenSearch indices are still derived state and can be rebuilt from
Magento, but snapshots reduce recovery time for service-level incidents.

### GCS Buckets

Use separate environment buckets. The Phase 5 contract assumes the validated
`Vapewholesaleusa_GcsRemoteStorage` path from ECOM-60, not `gcsfuse`.

| Bucket role | Purpose | Required behavior |
| --- | --- | --- |
| media | Magento remote media origin | read/write/delete through validated adapter |
| assets/import | import staging, generated handoff artifacts, non-secret operational files | no application secrets |

IAM should be bucket-scoped where practical:

| Principal | Media bucket | Assets/import bucket |
| --- | --- | --- |
| web/worker runtime identity | object read/write needed by Magento media path | read if needed |
| release identity | object read/write + sync/import duties | read/write |
| human operators | least privilege, usually no broad admin by default | least privilege |

Media protection:

- enable soft delete on production media buckets;
- use lifecycle rules for retained noncurrent/soft-deleted objects;
- do not treat object storage as a substitute for database backup;
- test restore of representative media objects, not only backup existence.

ECOM-60 remains the implementation dependency for the GCS-backed media path.
That ticket is now complete locally, but staging must still prove production-like
behavior before ECOM-4 relies on it.

### Secret Manager

Secrets are environment-scoped and named predictably:

```text
magento/stg/db/app-password
magento/stg/db/release-password
magento/stg/redis/auth
magento/stg/opensearch/app-password
magento/stg/composer/auth-json
magento/stg/app/crypt-key
magento/stg/integrations/<name>
```

Production uses the same path structure under `magento/prod/...`.

Required classes:

- database runtime and release credentials;
- Redis auth material if enabled;
- OpenSearch runtime credentials;
- Composer/vendor auth for builds only;
- Magento crypt key and other app secrets;
- third-party integration credentials.

IAM contract:

- web and worker identities can access only runtime secrets they need;
- release identity can access runtime secrets plus release-only database
  credentials;
- CI/build identity can access Composer/vendor auth but not production runtime
  secrets;
- human admin access is exceptional and auditable.

## Backup, Restore, and Retention

| State | Protection | Restore expectation |
| --- | --- | --- |
| Cloud SQL | automated backups + PITR | restore to a new instance first, validate, then cut over |
| GCS media | soft delete plus lifecycle policy | restore representative deleted/replaced objects and validate frontend read path |
| Redis | ephemeral operational state | rebuild caches; do not depend on Redis as a backup source |
| OpenSearch | rebuildable derived index | rebuild from Magento database; provider backups help service recovery but are not the source of truth |

Restore drills should be documented per environment. Production should not rely
on “backup enabled” as proof of recoverability.

## IaC Boundary for the Next Slice

The initial Terraform scaffold lives in `infra/terraform/`.

The first IaC implementation should create modules or stacks for:

1. private services access / required networking inputs;
2. private Cloud NAT for private GCE bootstrap and package/API egress;
3. Cloud SQL;
4. Memorystore Redis;
5. GCS buckets + IAM;
6. Secret Manager secret containers + IAM;
7. OpenSearch compute, disks, firewalling, service accounts, and snapshot
   storage.

Inputs that should be variables from day one:

- `environment`;
- `region`;
- VPC/network identifiers;
- maintenance window;
- backup/PITR retention;
- database tier/HA flags;
- Redis tier/size/TLS flags;
- OpenSearch machine types/node counts/version/private connectivity;
- bucket names and lifecycle retention.

## Open Decisions Before IaC

| Decision | Current recommendation | Why it remains open |
| --- | --- | --- |
| Single project vs separate project per environment | separate projects | depends on current GCP landing-zone reality |
| Redis TLS at first staging cut | enable if image/client validation passes | verify PHP Redis TLS path before freezing |
| Exact OpenSearch machine/node sizing | parameterize first | depends on catalog size and budget |
| OpenSearch topology | start staging small, production HA | confirm desired master/data split and ops budget |
| Production media retention window | choose during IaC review | depends on recovery objective and storage budget |

## Acceptance-Criteria Mapping

| Linear criterion | Contract answer |
| --- | --- |
| staging/prod separation | explicit environment boundary and naming |
| Cloud SQL private IP | required direct private-IP connectivity |
| Redis blast-radius protection | prefix separation; routine cache clean excludes sessions/locks |
| OpenSearch compatible topology/version | self-managed on GCP + OpenSearch `3` |
| GCS media dependency | tied to ECOM-60 validated adapter path |
| backup/restore expectations | documented per service with restore behavior |
