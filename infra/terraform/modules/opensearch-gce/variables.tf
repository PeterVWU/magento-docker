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

variable "node_private_ips" {
  description = "Optional fixed private IPs for OpenSearch nodes, indexed to node_count. Leave empty to let GCE allocate."
  type        = list(string)
  default     = []

  validation {
    condition     = length(var.node_private_ips) == 0 || length(var.node_private_ips) == var.node_count
    error_message = "node_private_ips must be empty or contain one IP per OpenSearch node."
  }
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

variable "security_hardening_enabled" {
  description = "Whether to replace packaged OpenSearch demo security material with reviewed TLS and users from Secret Manager."
  type        = bool
  default     = true
}

variable "tls_ca_cert_secret_id" {
  description = "Secret Manager secret ID containing the PEM CA certificate used to trust OpenSearch node/admin certificates."
  type        = string
}

variable "tls_node_cert_secret_id" {
  description = "Secret Manager secret ID containing the PEM certificate used by OpenSearch HTTP and transport TLS."
  type        = string
}

variable "tls_node_key_secret_id" {
  description = "Secret Manager secret ID containing the PEM private key for the OpenSearch node certificate."
  type        = string
}

variable "tls_admin_cert_secret_id" {
  description = "Secret Manager secret ID containing the PEM admin client certificate for securityadmin and break-glass maintenance."
  type        = string
}

variable "tls_admin_key_secret_id" {
  description = "Secret Manager secret ID containing the PEM private key for the admin client certificate."
  type        = string
}

variable "admin_distinguished_name" {
  description = "Distinguished name embedded in the admin client certificate."
  type        = string
  default     = "CN=magento-opensearch-admin"
}

variable "node_distinguished_name" {
  description = "Distinguished name embedded in the OpenSearch node certificate."
  type        = string
  default     = "CN=magento-opensearch-node"
}

variable "app_password_secret_id" {
  description = "Secret Manager secret ID containing the Magento OpenSearch application user password."
  type        = string
}

variable "operator_password_secret_id" {
  description = "Secret Manager secret ID containing the day-to-day OpenSearch operator user password."
  type        = string
}

variable "breakglass_password_secret_id" {
  description = "Secret Manager secret ID containing the emergency break-glass OpenSearch user password."
  type        = string
}

variable "magento_index_prefix" {
  description = "Magento OpenSearch index prefix; the Magento application role is limited to indices matching this prefix."
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
