locals {
  environment = "prod"
  name_prefix = "magento-prod"

  labels = merge(var.labels, {
    app         = "magento"
    environment = local.environment
    phase       = "ecom-6"
  })

  runtime_secret_ids = toset([
    "magento-prod-db-app-password",
    "magento-prod-redis-auth",
    "magento-prod-opensearch-app-password",
    "magento-prod-app-crypt-key",
  ])

  release_secret_ids = toset([
    "magento-prod-db-release-password",
  ])

  build_secret_ids = toset([
    "magento-prod-composer-auth-json",
  ])

  opensearch_admin_secret_id          = "magento-prod-opensearch-admin-password"
  opensearch_tls_ca_cert_secret_id    = "magento-prod-opensearch-tls-ca-cert"
  opensearch_tls_node_cert_secret_id  = "magento-prod-opensearch-tls-node-cert"
  opensearch_tls_node_key_secret_id   = "magento-prod-opensearch-tls-node-key"
  opensearch_tls_admin_cert_secret_id = "magento-prod-opensearch-tls-admin-cert"
  opensearch_tls_admin_key_secret_id  = "magento-prod-opensearch-tls-admin-key"
  opensearch_operator_secret_id       = "magento-prod-opensearch-operator-password"
  opensearch_breakglass_secret_id     = "magento-prod-opensearch-breakglass-password"

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
