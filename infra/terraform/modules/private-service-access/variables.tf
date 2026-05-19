variable "name" {
  description = "Name for the private services access reserved range."
  type        = string
}

variable "network_self_link" {
  description = "Self link of the VPC network used by Magento runtime services."
  type        = string
}

variable "prefix_length" {
  description = "CIDR prefix length for the service networking reserved range."
  type        = number
  default     = 16
}

variable "labels" {
  description = "Labels to apply where supported."
  type        = map(string)
  default     = {}
}
