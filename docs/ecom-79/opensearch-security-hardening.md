# ECOM-79 OpenSearch Security Hardening

Production OpenSearch must not rely on the OpenSearch package's demo TLS
certificates, demo users, or broad default access. The `opensearch-gce` module
now bootstraps explicit TLS material and reviewed internal users from Secret
Manager before applying the OpenSearch Security plugin configuration.

## Secret Manager Inputs

Each environment owns separate Secret Manager containers. Terraform creates the
containers and grants the OpenSearch VM service account `secretAccessor`, but
operators must add secret versions before rolling nodes.

| Purpose | Staging secret | Production secret |
| --- | --- | --- |
| Initial package admin password | `magento-stg-opensearch-admin-password` | `magento-prod-opensearch-admin-password` |
| Magento runtime password | `magento-stg-opensearch-app-password` | `magento-prod-opensearch-app-password` |
| Operator password | `magento-stg-opensearch-operator-password` | `magento-prod-opensearch-operator-password` |
| Break-glass password | `magento-stg-opensearch-breakglass-password` | `magento-prod-opensearch-breakglass-password` |
| CA certificate PEM | `magento-stg-opensearch-tls-ca-cert` | `magento-prod-opensearch-tls-ca-cert` |
| Node certificate PEM | `magento-stg-opensearch-tls-node-cert` | `magento-prod-opensearch-tls-node-cert` |
| Node private key PEM | `magento-stg-opensearch-tls-node-key` | `magento-prod-opensearch-tls-node-key` |
| Admin client certificate PEM | `magento-stg-opensearch-tls-admin-cert` | `magento-prod-opensearch-tls-admin-cert` |
| Admin client private key PEM | `magento-stg-opensearch-tls-admin-key` | `magento-prod-opensearch-tls-admin-key` |

Certificate source is an internal CA controlled by the infrastructure operator.
Do not use the package-generated demo CA. The current module expects these
distinguished names:

```text
OpenSearch node certificate: CN=magento-opensearch-node
OpenSearch admin certificate: CN=magento-opensearch-admin
```

The same node certificate may be used for all nodes while hostname verification
is disabled on transport TLS. If per-node certificates are introduced later,
add each node DN to `node_distinguished_name` handling before rotation.

## Bootstrap Behavior

At VM startup the module:

1. Installs OpenSearch 3.x and `repository-gcs`.
2. Fetches the PEM certificates, PEM keys, and user passwords from Secret
   Manager using the OpenSearch VM service account.
3. Writes TLS files under `/etc/opensearch/ecom-security`.
4. Rewrites the managed block in `/etc/opensearch/opensearch.yml` to use the
   managed CA/node/admin certificates for HTTP and transport TLS.
5. Generates OpenSearch Security `internal_users.yml`, `roles.yml`, and
   `roles_mapping.yml` under `/etc/opensearch/opensearch-security`.
6. Restarts OpenSearch and applies security config with `securityadmin.sh`
   using the admin client certificate.
7. Registers the GCS snapshot repository with the `magento_operator` user.

The package admin password remains a first-boot guardrail, not a normal
operator path. Rotate it after the first successful hardened bootstrap.

## Users And Roles

`magento_app` is the Magento runtime user. It is mapped only to
`magento_index_owner`, which grants cluster composite operations, cluster
monitoring, and index management against indices matching
`magento_opensearch_index_prefix`.

`magento_operator` is for routine operations: cluster health, index monitoring
for Magento indices, and snapshot repository management.

`magento_breakglass` has full cluster and index permissions. Use it only for an
incident, record the reason in Linear or the incident channel, and rotate the
password immediately after use.

## Rotation

Password rotation:

1. Add a new Secret Manager version for the relevant password.
2. Roll one OpenSearch node.
3. Confirm `securityadmin.sh` reapplies the hashes successfully.
4. Validate cluster health and Magento search.
5. Continue one node at a time.

Certificate rotation:

1. Add new CA/node/admin certificate and key versions in Secret Manager.
2. If rotating the CA, ensure both the node and admin certs chain to the new CA.
3. Roll one node and confirm it rejoins the cluster.
4. Validate HTTPS, auth, cluster health, Magento search, and snapshots.
5. Continue one node at a time.

For emergency break-glass use, rotate `magento-<env>-opensearch-breakglass-password`
as soon as the incident is closed.

## Validation

Run these checks from a host that can reach the private OpenSearch endpoint.
Use the environment's real private IP and credentials from Secret Manager.

```bash
curl --fail --cacert ca.pem -u "magento_operator:${OPENSEARCH_OPERATOR_PASSWORD}" \
  "https://OPENSEARCH_PRIVATE_IP:9200/_cluster/health?pretty"

curl --fail --cacert ca.pem -u "magento_operator:${OPENSEARCH_OPERATOR_PASSWORD}" \
  "https://OPENSEARCH_PRIVATE_IP:9200/_snapshot/gcs-magento"
```

Validate Magento behavior from the release/runtime environment:

```bash
bin/magento indexer:reindex catalogsearch_fulltext
bin/magento cache:clean
bin/magento catalog:images:resize --help >/dev/null
```

Then verify a storefront or GraphQL catalog search returns expected products.
Production rollout from ECOM-6 remains blocked until staging completes these
checks with the hardened configuration, or the waiver is explicitly recorded in
Linear.
