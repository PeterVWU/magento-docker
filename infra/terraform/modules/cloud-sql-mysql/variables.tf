variable "name" {
  type = string
}
variable "project_id" {
  type = string
}
variable "region" {
  type = string
}
variable "database_version" {
  type    = string
  default = "MYSQL_8_4"
}
variable "tier" {
  type = string
}

variable "edition" {
  description = "Cloud SQL edition. Enterprise supports db-custom tiers; Enterprise Plus requires db-perf-optimized tiers."
  type        = string
  default     = "ENTERPRISE"
}
variable "availability_type" {
  type    = string
  default = "ZONAL"
}
variable "disk_size_gb" {
  type    = number
  default = 100
}
variable "disk_type" {
  type    = string
  default = "PD_SSD"
}
variable "database_name" {
  type    = string
  default = "magento"
}
variable "network_self_link" {
  type = string
}
variable "maintenance_day" {
  type    = number
  default = 7
}
variable "maintenance_hour" {
  type    = number
  default = 9
}
variable "backup_start_time" {
  type    = string
  default = "09:00"
}
variable "retained_backups" {
  type    = number
  default = 14
}
variable "deletion_protection" {
  type    = bool
  default = true
}
variable "app_user_name" {
  type    = string
  default = "magento_app"
}
variable "release_user_name" {
  type    = string
  default = "magento_release"
}
variable "app_user_password" {
  type      = string
  sensitive = true
}
variable "release_user_password" {
  type      = string
  sensitive = true
}
variable "labels" {
  type    = map(string)
  default = {}
}
