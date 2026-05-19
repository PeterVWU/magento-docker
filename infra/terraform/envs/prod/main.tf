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

resource "google_secret_manager_secret_iam_member" "opensearch_admin_access" {
  project   = var.project_id
  secret_id = module.secrets.secret_ids[local.opensearch_admin_secret_id]
  role      = "roles/secretmanager.secretAccessor"
  member    = module.service_accounts.members.opensearch
}

module "opensearch" {
  source                   = "../../modules/opensearch-gce"
  name                     = "${local.name_prefix}-search"
  project_id               = var.project_id
  zone                     = var.zone
  network                  = var.network_self_link
  subnetwork               = var.subnetwork_self_link
  node_count               = var.opensearch_node_count
  machine_type             = var.opensearch_machine_type
  data_disk_size_gb        = var.opensearch_data_disk_size_gb
  service_account_email    = module.service_accounts.emails.opensearch
  opensearch_version       = var.opensearch_version
  heap_size                = var.opensearch_heap_size
  admin_password_secret_id = local.opensearch_admin_secret_id
  snapshot_bucket_name     = module.buckets.opensearch_snapshot_bucket_name
  snapshot_base_path       = "${local.environment}/snapshots"
  app_source_ranges        = var.web_mig_source_ranges
  app_source_tags          = var.web_mig_source_tags
  cluster_tag              = "magento-prod-opensearch"
  labels                   = local.labels

  depends_on = [module.project_services, module.cloud_nat]
}
