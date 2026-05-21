variable "enabled" {
  description = "Whether to create the external HTTPS load balancer resources."
  type        = bool
  default     = true
}

variable "name_prefix" {
  type = string
}

variable "project_id" {
  type = string
}

variable "backend_instance_group" {
  description = "Instance group self link to attach to the backend service."
  type        = string
}

variable "health_check" {
  description = "Global health check self link for the backend service."
  type        = string
}

variable "backend_port_name" {
  type    = string
  default = "http"
}

variable "backend_timeout_sec" {
  type    = number
  default = 30
}

variable "backend_max_utilization" {
  type    = number
  default = 0.8
}

variable "enable_cdn" {
  type    = bool
  default = false
}

variable "security_policy" {
  description = "Optional Cloud Armor security policy self link."
  type        = string
  default     = null
}

variable "managed_ssl_certificate_domains" {
  description = "Domains for a Google-managed certificate."
  type        = list(string)
  default     = []
}

variable "ssl_certificate_self_links" {
  description = "Existing SSL certificate self links to attach to the HTTPS proxy."
  type        = list(string)
  default     = []
}

variable "enable_http_redirect" {
  type    = bool
  default = true
}

variable "dns_managed_zone" {
  description = "Optional Cloud DNS managed zone name for the A record."
  type        = string
  default     = null
}

variable "dns_record_name" {
  description = "Optional fully qualified DNS record name, ending with a dot."
  type        = string
  default     = null
}

variable "labels" {
  type    = map(string)
  default = {}
}
