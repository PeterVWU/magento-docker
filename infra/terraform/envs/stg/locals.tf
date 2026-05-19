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

  opensearch_admin_secret_id = "magento-stg-opensearch-admin-password"

  opensearch_secret_ids = toset([
    local.opensearch_admin_secret_id,
  ])

  required_project_services = toset([
    "compute.googleapis.com",
    "iam.googleapis.com",
    "logging.googleapis.com",
    "servicenetworking.googleapis.com",
    "sqladmin.googleapis.com",
    "redis.googleapis.com",
    "secretmanager.googleapis.com",
    "storage.googleapis.com",
  ])

  secret_ids = setunion(
    local.runtime_secret_ids,
    local.release_secret_ids,
    local.build_secret_ids,
    local.opensearch_secret_ids,
  )
}
