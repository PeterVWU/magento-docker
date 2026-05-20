variable "name_prefix" {
  type = string
}

variable "project_id" {
  type = string
}

variable "region" {
  type = string
}

variable "zone" {
  type = string
}

variable "network" {
  type = string
}

variable "subnetwork" {
  type = string
}

variable "runtime_image" {
  description = "Immutable Magento runtime container image, normally a SHA-tagged Artifact Registry image."
  type        = string
}

variable "image_sha" {
  description = "Commit SHA or image version emitted into instance metadata and container environment."
  type        = string
}

variable "environment" {
  type = string
}

variable "web_machine_type" {
  type = string
}

variable "worker_machine_type" {
  type = string
}

variable "web_target_size" {
  type = number
}

variable "worker_target_size" {
  type = number
}

variable "boot_image" {
  type    = string
  default = "projects/debian-cloud/global/images/family/debian-12"
}

variable "boot_disk_size_gb" {
  type    = number
  default = 30
}

variable "service_account_email" {
  type = string
}

variable "release_service_account_email" {
  type = string
}

variable "db_host" {
  type = string
}

variable "db_name" {
  type = string
}

variable "db_user" {
  type = string
}

variable "db_release_user" {
  type = string
}

variable "db_password_secret_id" {
  type = string
}

variable "db_release_password_secret_id" {
  type = string
}

variable "redis_host" {
  type = string
}

variable "redis_port" {
  type = number
}

variable "redis_auth_secret_id" {
  type = string
}

variable "opensearch_host" {
  type = string
}

variable "opensearch_port" {
  type    = number
  default = 9200
}

variable "opensearch_password_secret_id" {
  type = string
}

variable "crypt_key_secret_id" {
  type = string
}

variable "media_bucket_name" {
  type = string
}

variable "assets_bucket_name" {
  type = string
}

variable "web_source_ranges" {
  description = "Source CIDR ranges allowed to reach web instances on the application port."
  type        = list(string)
  default     = []
}

variable "web_source_tags" {
  description = "Source network tags allowed to reach web instances on the application port."
  type        = list(string)
  default     = []
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

variable "labels" {
  type    = map(string)
  default = {}
}
