locals {
  environment = "stg"
  name_prefix = "magento-stg"

  labels = merge(var.labels, {
    app         = "magento"
    environment = local.environment
    phase       = "ecom-6"
  })

  runtime_secret_ids = toset([
    "magento-stg-db-app-password",
    "magento-stg-redis-auth",
    "magento-stg-opensearch-app-password",
    "magento-stg-app-crypt-key",
  ])

  release_secret_ids = toset([
    "magento-stg-db-release-password",
  ])

  build_secret_ids = toset([
    "magento-stg-composer-auth-json",
  ])

  opensearch_admin_secret_id          = "magento-stg-opensearch-admin-password"
  opensearch_tls_ca_cert_secret_id    = "magento-stg-opensearch-tls-ca-cert"
  opensearch_tls_node_cert_secret_id  = "magento-stg-opensearch-tls-node-cert"
  opensearch_tls_node_key_secret_id   = "magento-stg-opensearch-tls-node-key"
  opensearch_tls_admin_cert_secret_id = "magento-stg-opensearch-tls-admin-cert"
  opensearch_tls_admin_key_secret_id  = "magento-stg-opensearch-tls-admin-key"
  opensearch_operator_secret_id       = "magento-stg-opensearch-operator-password"
  opensearch_breakglass_secret_id     = "magento-stg-opensearch-breakglass-password"
  media_hmac_key_secret_id            = "magento-stg-media-hmac-key"
  media_hmac_secret_secret_id         = "magento-stg-media-hmac-secret"

  media_hmac_secret_ids = toset([
    local.media_hmac_key_secret_id,
    local.media_hmac_secret_secret_id,
  ])

  opensearch_secret_ids = toset([
    local.opensearch_admin_secret_id,
    local.opensearch_tls_ca_cert_secret_id,
    local.opensearch_tls_node_cert_secret_id,
    local.opensearch_tls_node_key_secret_id,
    local.opensearch_tls_admin_cert_secret_id,
    local.opensearch_tls_admin_key_secret_id,
    local.opensearch_operator_secret_id,
    local.opensearch_breakglass_secret_id,
  ])

  base_project_services = toset([
    "compute.googleapis.com",
    "iam.googleapis.com",
    "logging.googleapis.com",
    "servicenetworking.googleapis.com",
    "sqladmin.googleapis.com",
    "redis.googleapis.com",
    "secretmanager.googleapis.com",
    "storage.googleapis.com",
  ])

  dns_project_services = var.external_https_lb_dns_managed_zone != null ? toset([
    "dns.googleapis.com",
  ]) : toset([])

  required_project_services = setunion(local.base_project_services, local.dns_project_services)

  secret_ids = setunion(
    local.runtime_secret_ids,
    local.release_secret_ids,
    local.build_secret_ids,
    local.opensearch_secret_ids,
  )
}
