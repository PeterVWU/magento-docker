variable "name" {
  type = string
}

variable "project_id" {
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

variable "node_count" {
  type    = number
  default = 3
}

variable "machine_type" {
  type = string
}

variable "boot_image" {
  type    = string
  default = "projects/debian-cloud/global/images/family/debian-12"
}

variable "boot_disk_size_gb" {
  type    = number
  default = 50
}

variable "data_disk_size_gb" {
  type    = number
  default = 100
}

variable "data_disk_type" {
  type    = string
  default = "pd-ssd"
}

variable "service_account_email" {
  type = string
}

variable "app_source_ranges" {
  type    = list(string)
  default = []
}

variable "app_source_tags" {
  type    = list(string)
  default = []
}

variable "cluster_tag" {
  type    = string
  default = "opensearch"
}

variable "opensearch_version" {
  description = "OpenSearch package version to install from the 3.x APT repository."
  type        = string
  default     = "3.6.0"
}

variable "heap_size" {
  description = "OpenSearch JVM heap size, e.g. 2g. Keep at or below half of VM memory."
  type        = string
  default     = "2g"
}

variable "admin_password_secret_id" {
  description = "Secret Manager secret ID containing the initial/admin OpenSearch password."
  type        = string
}

variable "snapshot_bucket_name" {
  description = "GCS bucket used by the repository-gcs snapshot repository."
  type        = string
}

variable "snapshot_base_path" {
  description = "Base path inside the snapshot bucket for this cluster."
  type        = string
  default     = "snapshots"
}

variable "labels" {
  type    = map(string)
  default = {}
}
