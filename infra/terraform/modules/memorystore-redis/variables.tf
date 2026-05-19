variable "name" {
  type = string
}
variable "project_id" {
  type = string
}
variable "region" {
  type = string
}
variable "memory_size_gb" {
  type = number
}
variable "tier" {
  type    = string
  default = "STANDARD_HA"
}
variable "redis_version" {
  type    = string
  default = "REDIS_7_2"
}
variable "authorized_network" {
  type = string
}
variable "connect_mode" {
  type    = string
  default = "PRIVATE_SERVICE_ACCESS"
}
variable "auth_enabled" {
  type    = bool
  default = true
}
variable "transit_encryption_mode" {
  type    = string
  default = "DISABLED"
}
variable "labels" {
  type    = map(string)
  default = {}
}
