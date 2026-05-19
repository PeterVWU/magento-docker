variable "project_id" {
  type = string
}
variable "secret_ids" {
  type = set(string)
}
variable "labels" {
  type    = map(string)
  default = {}
}
variable "runtime_accessor_members" {
  type    = set(string)
  default = []
}
variable "release_accessor_members" {
  type    = set(string)
  default = []
}
variable "build_accessor_members" {
  type    = set(string)
  default = []
}
variable "runtime_secret_ids" {
  type    = set(string)
  default = []
}
variable "release_secret_ids" {
  type    = set(string)
  default = []
}
variable "build_secret_ids" {
  type    = set(string)
  default = []
}
