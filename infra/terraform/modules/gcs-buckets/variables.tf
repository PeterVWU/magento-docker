variable "project_id" {
  type = string
}
variable "location" {
  type = string
}
variable "media_bucket_name" {
  type = string
}
variable "assets_bucket_name" {
  type = string
}
variable "opensearch_snapshot_bucket_name" {
  type = string
}
variable "force_destroy" {
  type    = bool
  default = false
}
variable "soft_delete_retention_seconds" {
  type    = number
  default = 604800
}
variable "labels" {
  type    = map(string)
  default = {}
}
variable "media_object_admin_members" {
  type    = set(string)
  default = []
}
variable "assets_object_admin_members" {
  type    = set(string)
  default = []
}
variable "assets_object_viewer_members" {
  type    = set(string)
  default = []
}
variable "opensearch_snapshot_object_admin_members" {
  type    = set(string)
  default = []
}
