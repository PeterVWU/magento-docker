output "cloud_sql_private_ip" {
  value = module.cloud_sql.private_ip_address
}
output "cloud_sql_connection_name" {
  value = module.cloud_sql.connection_name
}
output "redis_host" {
  value = module.redis.host
}
output "redis_port" {
  value = module.redis.port
}
output "media_bucket_name" {
  value = module.buckets.media_bucket_name
}
output "assets_bucket_name" {
  value = module.buckets.assets_bucket_name
}
output "opensearch_snapshot_bucket_name" {
  value = module.buckets.opensearch_snapshot_bucket_name
}
output "secret_names" {
  value = module.secrets.secret_names
}
output "opensearch_node_private_ips" {
  value = module.opensearch.node_private_ips
}

output "service_account_emails" {
  value = module.service_accounts.emails
}

output "enabled_project_services" {
  value = module.project_services.enabled_services
}

output "web_instance_group" {
  value = module.compute_runtime.web_instance_group
}

output "web_instance_template" {
  value = module.compute_runtime.web_instance_template
}

output "worker_instance_group" {
  value = module.compute_runtime.worker_instance_group
}

output "worker_instance_template" {
  value = module.compute_runtime.worker_instance_template
}

output "release_instance_template" {
  value = module.compute_runtime.release_instance_template
}

output "web_health_check" {
  value = module.compute_runtime.web_health_check
}
