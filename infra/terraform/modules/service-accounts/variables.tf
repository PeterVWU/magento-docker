variable "project_id" {
  type = string
}

variable "environment" {
  type = string
}

variable "prefix" {
  description = "Short service account prefix, for example magento-stg."
  type        = string
}

variable "labels" {
  type    = map(string)
  default = {}
}
