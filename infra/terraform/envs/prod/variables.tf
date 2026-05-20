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
  default = "e2-standard-4"
}

variable "worker_machine_type" {
  type    = string
  default = "e2-standard-2"
}

variable "web_target_size" {
  type    = number
  default = 3
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
