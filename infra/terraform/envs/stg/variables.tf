variable "project_id" {
  type = string
}
variable "impersonate_service_account" {
  type    = string
  default = null
}
variable "region" {
  type = string
}
variable "zone" {
  type = string
}
variable "network_self_link" {
  type = string
}
variable "network_name" {
  type = string
}
variable "subnetwork_self_link" {
  type = string
}
variable "labels" {
  type    = map(string)
  default = {}
}

variable "cloud_sql_tier" {
  type = string
}
variable "cloud_sql_disk_size_gb" {
  type = number
}
variable "cloud_sql_app_password" {
  type      = string
  sensitive = true
}
variable "cloud_sql_release_password" {
  type      = string
  sensitive = true
}

variable "redis_memory_size_gb" {
  type = number
}
variable "redis_transit_encryption_mode" {
  type    = string
  default = "DISABLED"
}

variable "opensearch_node_count" {
  type = number
}
variable "opensearch_machine_type" {
  type = string
}
variable "opensearch_data_disk_size_gb" {
  type = number
}

variable "opensearch_version" {
  type    = string
  default = "3.6.0"
}
variable "opensearch_heap_size" {
  type    = string
  default = "2g"
}
variable "web_mig_source_ranges" {
  type    = list(string)
  default = []
}
variable "web_mig_source_tags" {
  type    = list(string)
  default = []
}

variable "runtime_image" {
  description = "SHA-tagged Magento runtime container image."
  type        = string
}

variable "runtime_image_sha" {
  description = "Commit SHA or image version used for runtime metadata."
  type        = string
}

variable "web_machine_type" {
  type    = string
  default = "e2-standard-2"
}

variable "worker_machine_type" {
  type    = string
  default = "e2-standard-2"
}

variable "web_target_size" {
  type    = number
  default = 2
}

variable "worker_target_size" {
  type    = number
  default = 1
}

variable "web_runtime_source_ranges" {
  type    = list(string)
  default = []
}

variable "web_runtime_source_tags" {
  type    = list(string)
  default = []
}

variable "worker_consumers" {
  type    = list(string)
  default = []
}

variable "worker_consumer_max_messages" {
  type    = number
  default = 10000
}

variable "cron_interval_seconds" {
  type    = number
  default = 60
}

variable "magento_base_url" {
  type    = string
  default = "http://localhost:8080/"
}

variable "magento_secure_base_url" {
  type    = string
  default = "http://localhost:8080/"
}

variable "magento_opensearch_index_prefix" {
  type    = string
  default = "magento2"
}

variable "enable_external_https_lb" {
  type    = bool
  default = false
}

variable "external_https_lb_domains" {
  description = "Domains for the Google-managed HTTPS certificate."
  type        = list(string)
  default     = []
}

variable "external_https_lb_existing_certificate_self_links" {
  description = "Existing SSL certificate self links to attach instead of, or in addition to, the managed certificate."
  type        = list(string)
  default     = []
}

variable "external_https_lb_dns_managed_zone" {
  description = "Optional Cloud DNS managed zone name for the staging A record."
  type        = string
  default     = null
}

variable "external_https_lb_dns_record_name" {
  description = "Optional fully qualified DNS record name, ending with a dot."
  type        = string
  default     = null
}

variable "external_https_lb_enable_http_redirect" {
  type    = bool
  default = true
}

variable "external_https_lb_enable_cdn" {
  type    = bool
  default = false
}

variable "external_https_lb_security_policy" {
  description = "Optional Cloud Armor security policy self link."
  type        = string
  default     = null
}

variable "runtime_secret_accessor_members" {
  type    = set(string)
  default = []
}
variable "release_secret_accessor_members" {
  type    = set(string)
  default = []
}
variable "build_secret_accessor_members" {
  type    = set(string)
  default = []
}
