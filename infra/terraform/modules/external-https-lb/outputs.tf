output "ip_address" {
  value = try(google_compute_global_address.default[0].address, null)
}

output "backend_service" {
  value = try(google_compute_backend_service.default[0].self_link, null)
}

output "url_map" {
  value = try(google_compute_url_map.default[0].self_link, null)
}

output "https_forwarding_rule" {
  value = try(google_compute_global_forwarding_rule.https[0].self_link, null)
}

output "http_forwarding_rule" {
  value = try(google_compute_global_forwarding_rule.http_redirect[0].self_link, null)
}

output "managed_ssl_certificate" {
  value = try(google_compute_managed_ssl_certificate.default[0].self_link, null)
}

output "dns_record_name" {
  value = try(google_dns_record_set.a[0].name, null)
}
