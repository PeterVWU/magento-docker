variable "project_id" {
  type = string
}

variable "services" {
  description = "GCP service APIs required by this environment."
  type        = set(string)
}

variable "disable_on_destroy" {
  description = "Whether APIs should be disabled when this config is destroyed. Keep false for shared projects."
  type        = bool
  default     = false
}
