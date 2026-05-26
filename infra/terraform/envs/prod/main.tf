module "project_services" {
  source     = "../../modules/project-services"
  project_id = var.project_id
  services   = local.required_project_services
}

module "service_accounts" {
  source      = "../../modules/service-accounts"
  project_id  = var.project_id
  environment = local.environment
  prefix      = local.name_prefix
  labels      = local.labels

  depends_on = [module.project_services]
}


resource "google_project_iam_member" "service_account_log_writers" {
  for_each = module.service_accounts.members

  project = var.project_id
  role    = "roles/logging.logWriter"
  member  = each.value

  depends_on = [module.project_services, module.service_accounts]
}

resource "google_project_iam_member" "runtime_artifact_registry_readers" {
  for_each = {
    runtime = module.service_accounts.members.runtime
    release = module.service_accounts.members.release
  }

  project = var.project_id
  role    = "roles/artifactregistry.reader"
  member  = each.value

  depends_on = [module.project_services, module.service_accounts]
}

module "cloud_nat" {
  source               = "../../modules/cloud-nat"
  project_id           = var.project_id
  region               = var.region
  network_self_link    = var.network_self_link
  subnetwork_self_link = var.subnetwork_self_link
  router_name          = "${local.name_prefix}-private-egress"
  nat_name             = "${local.name_prefix}-private-egress"

  depends_on = [module.project_services]
}

module "private_service_access" {
  source            = "../../modules/private-service-access"
  name              = "${local.name_prefix}-private-services"
  network_self_link = var.network_self_link

  depends_on = [module.project_services]
}

module "cloud_sql" {
  source                = "../../modules/cloud-sql-mysql"
  name                  = "${local.name_prefix}-mysql"
  project_id            = var.project_id
  region                = var.region
  tier                  = var.cloud_sql_tier
  availability_type     = "REGIONAL"
  disk_size_gb          = var.cloud_sql_disk_size_gb
  network_self_link     = var.network_self_link
  app_user_password     = var.cloud_sql_app_password
  release_user_password = var.cloud_sql_release_password
  database_flags        = { log_bin_trust_function_creators = "on" }
  deletion_protection   = true
  labels                = local.labels

  depends_on = [module.project_services, module.private_service_access]
}

module "redis" {
  source                  = "../../modules/memorystore-redis"
  name                    = "${local.name_prefix}-redis"
  project_id              = var.project_id
  region                  = var.region
  tier                    = "STANDARD_HA"
  memory_size_gb          = var.redis_memory_size_gb
  authorized_network      = var.network_self_link
  transit_encryption_mode = var.redis_transit_encryption_mode
  labels                  = local.labels

  depends_on = [module.project_services, module.private_service_access]
}

module "buckets" {
  source                                   = "../../modules/gcs-buckets"
  project_id                               = var.project_id
  location                                 = var.region
  media_bucket_name                        = "vwu-magento-prod-media"
  assets_bucket_name                       = "vwu-magento-prod-assets"
  opensearch_snapshot_bucket_name          = "vwu-magento-prod-opensearch-snapshots"
  force_destroy                            = false
  media_object_admin_members               = toset([module.service_accounts.members.runtime, module.service_accounts.members.release])
  assets_object_admin_members              = toset([module.service_accounts.members.release])
  assets_object_viewer_members             = toset([module.service_accounts.members.runtime])
  opensearch_snapshot_object_admin_members = toset([module.service_accounts.members.opensearch])
  labels                                   = local.labels

  depends_on = [module.project_services]
}

module "secrets" {
  source                   = "../../modules/secret-manager"
  project_id               = var.project_id
  secret_ids               = local.secret_ids
  runtime_secret_ids       = local.runtime_secret_ids
  release_secret_ids       = local.release_secret_ids
  build_secret_ids         = local.build_secret_ids
  runtime_accessor_members = var.runtime_secret_accessor_members
  release_accessor_members = var.release_secret_accessor_members
  build_accessor_members   = var.build_secret_accessor_members
  labels                   = local.labels

  depends_on = [module.project_services]
}

resource "google_secret_manager_secret_iam_member" "runtime_service_account_secret_access" {
  for_each = local.runtime_secret_ids

  project   = var.project_id
  secret_id = module.secrets.secret_ids[each.value]
  role      = "roles/secretmanager.secretAccessor"
  member    = module.service_accounts.members.runtime
}

resource "google_secret_manager_secret_iam_member" "release_service_account_secret_access" {
  for_each = setunion(local.release_secret_ids, local.runtime_secret_ids)

  project   = var.project_id
  secret_id = module.secrets.secret_ids[each.value]
  role      = "roles/secretmanager.secretAccessor"
  member    = module.service_accounts.members.release
}

resource "google_secret_manager_secret_iam_member" "opensearch_admin_access" {
  project   = var.project_id
  secret_id = module.secrets.secret_ids[local.opensearch_admin_secret_id]
  role      = "roles/secretmanager.secretAccessor"
  member    = module.service_accounts.members.opensearch
}

resource "google_secret_manager_secret_iam_member" "opensearch_extra_secret_access" {
  for_each = setsubtract(
    setunion(local.opensearch_secret_ids, toset(["magento-prod-opensearch-app-password"])),
    toset([local.opensearch_admin_secret_id]),
  )

  project   = var.project_id
  secret_id = module.secrets.secret_ids[each.value]
  role      = "roles/secretmanager.secretAccessor"
  member    = module.service_accounts.members.opensearch
}

module "opensearch" {
  source                        = "../../modules/opensearch-gce"
  name                          = "${local.name_prefix}-search"
  project_id                    = var.project_id
  zone                          = var.zone
  network                       = var.network_self_link
  subnetwork                    = var.subnetwork_self_link
  node_count                    = var.opensearch_node_count
  node_private_ips              = var.opensearch_node_private_ips
  machine_type                  = var.opensearch_machine_type
  data_disk_size_gb             = var.opensearch_data_disk_size_gb
  service_account_email         = module.service_accounts.emails.opensearch
  opensearch_version            = var.opensearch_version
  heap_size                     = var.opensearch_heap_size
  admin_password_secret_id      = local.opensearch_admin_secret_id
  tls_ca_cert_secret_id         = local.opensearch_tls_ca_cert_secret_id
  tls_node_cert_secret_id       = local.opensearch_tls_node_cert_secret_id
  tls_node_key_secret_id        = local.opensearch_tls_node_key_secret_id
  tls_admin_cert_secret_id      = local.opensearch_tls_admin_cert_secret_id
  tls_admin_key_secret_id       = local.opensearch_tls_admin_key_secret_id
  app_password_secret_id        = "magento-prod-opensearch-app-password"
  operator_password_secret_id   = local.opensearch_operator_secret_id
  breakglass_password_secret_id = local.opensearch_breakglass_secret_id
  magento_index_prefix          = var.magento_opensearch_index_prefix
  snapshot_bucket_name          = module.buckets.opensearch_snapshot_bucket_name
  snapshot_base_path            = "${local.environment}/snapshots"
  app_source_ranges             = var.web_mig_source_ranges
  app_source_tags               = var.web_mig_source_tags
  cluster_tag                   = "magento-prod-opensearch"
  labels                        = local.labels

  depends_on = [module.project_services, module.cloud_nat]
}

module "compute_runtime" {
  source                        = "../../modules/compute-runtime"
  name_prefix                   = local.name_prefix
  project_id                    = var.project_id
  region                        = var.region
  zone                          = var.zone
  network                       = var.network_self_link
  subnetwork                    = var.subnetwork_self_link
  runtime_image                 = var.runtime_image
  image_sha                     = var.runtime_image_sha
  environment                   = local.environment
  web_machine_type              = var.web_machine_type
  worker_machine_type           = var.worker_machine_type
  web_target_size               = var.web_target_size
  worker_target_size            = var.worker_target_size
  service_account_email         = module.service_accounts.emails.runtime
  release_service_account_email = module.service_accounts.emails.release
  db_host                       = module.cloud_sql.private_ip_address
  db_name                       = module.cloud_sql.database_name
  db_user                       = module.cloud_sql.app_user_name
  db_release_user               = module.cloud_sql.release_user_name
  db_password_secret_id         = "magento-prod-db-app-password"
  db_release_password_secret_id = "magento-prod-db-release-password"
  redis_host                    = module.redis.host
  redis_port                    = module.redis.port
  redis_auth_secret_id          = "magento-prod-redis-auth"
  opensearch_host               = "https://${module.opensearch.node_private_ips[0]}"
  opensearch_password_secret_id = "magento-prod-opensearch-app-password"
  opensearch_user               = "magento_app"
  opensearch_ssl_verify         = false
  crypt_key_secret_id           = "magento-prod-app-crypt-key"
  media_bucket_name             = module.buckets.media_bucket_name
  assets_bucket_name            = module.buckets.assets_bucket_name
  base_url                      = var.magento_base_url
  secure_base_url               = var.magento_secure_base_url
  opensearch_index_prefix       = var.magento_opensearch_index_prefix
  web_source_ranges             = var.web_runtime_source_ranges
  web_source_tags               = var.web_runtime_source_tags
  worker_consumers              = var.worker_consumers
  worker_consumer_max_messages  = var.worker_consumer_max_messages
  cron_interval_seconds         = var.cron_interval_seconds
  labels                        = merge(local.labels, { phase = "ecom-8" })

  depends_on = [module.project_services, module.cloud_nat, module.secrets]
}

module "external_https_lb" {
  source                          = "../../modules/external-https-lb"
  enabled                         = var.enable_external_https_lb
  name_prefix                     = local.name_prefix
  project_id                      = var.project_id
  backend_instance_group          = module.compute_runtime.web_instance_group
  health_check                    = module.compute_runtime.web_health_check
  managed_ssl_certificate_domains = var.external_https_lb_domains
  ssl_certificate_self_links      = var.external_https_lb_existing_certificate_self_links
  enable_http_redirect            = var.external_https_lb_enable_http_redirect
  enable_cdn                      = var.external_https_lb_enable_cdn
  security_policy                 = var.external_https_lb_security_policy
  dns_managed_zone                = var.external_https_lb_dns_managed_zone
  dns_record_name                 = var.external_https_lb_dns_record_name
  labels                          = merge(local.labels, { phase = "ecom-4" })

  depends_on = [module.project_services, module.compute_runtime]
}
