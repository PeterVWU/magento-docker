locals {
  create_dns_record = var.enabled && var.dns_managed_zone != null && var.dns_record_name != null
  certificate_self_links = concat(
    var.ssl_certificate_self_links,
    var.enabled && length(var.managed_ssl_certificate_domains) > 0 ? [google_compute_managed_ssl_certificate.default[0].self_link] : [],
  )
}

resource "google_compute_global_address" "default" {
  count = var.enabled ? 1 : 0

  name    = "${var.name_prefix}-lb-ip"
  project = var.project_id
  labels  = var.labels
}

resource "google_compute_managed_ssl_certificate" "default" {
  count = var.enabled && length(var.managed_ssl_certificate_domains) > 0 ? 1 : 0

  name    = "${var.name_prefix}-managed-cert"
  project = var.project_id

  managed {
    domains = var.managed_ssl_certificate_domains
  }
}

resource "google_compute_backend_service" "default" {
  count = var.enabled ? 1 : 0

  name                  = "${var.name_prefix}-web-backend"
  project               = var.project_id
  protocol              = "HTTP"
  port_name             = var.backend_port_name
  timeout_sec           = var.backend_timeout_sec
  load_balancing_scheme = "EXTERNAL_MANAGED"
  health_checks         = [var.health_check]
  enable_cdn            = var.enable_cdn
  security_policy       = var.security_policy

  backend {
    group           = var.backend_instance_group
    balancing_mode  = "UTILIZATION"
    capacity_scaler = 1.0
    max_utilization = var.backend_max_utilization
  }

  log_config {
    enable      = true
    sample_rate = 1.0
  }
}

resource "google_compute_url_map" "default" {
  count = var.enabled ? 1 : 0

  name            = "${var.name_prefix}-web"
  project         = var.project_id
  default_service = google_compute_backend_service.default[0].self_link
}

resource "google_compute_target_https_proxy" "default" {
  count = var.enabled ? 1 : 0

  name             = "${var.name_prefix}-https"
  project          = var.project_id
  url_map          = google_compute_url_map.default[0].self_link
  ssl_certificates = local.certificate_self_links

  lifecycle {
    precondition {
      condition     = length(local.certificate_self_links) > 0
      error_message = "Enable a managed certificate domain or provide at least one SSL certificate self link."
    }
  }
}

resource "google_compute_global_forwarding_rule" "https" {
  count = var.enabled ? 1 : 0

  name                  = "${var.name_prefix}-https"
  project               = var.project_id
  ip_address            = google_compute_global_address.default[0].address
  ip_protocol           = "TCP"
  load_balancing_scheme = "EXTERNAL_MANAGED"
  port_range            = "443"
  target                = google_compute_target_https_proxy.default[0].self_link
}

resource "google_compute_url_map" "http_redirect" {
  count = var.enabled && var.enable_http_redirect ? 1 : 0

  name    = "${var.name_prefix}-http-redirect"
  project = var.project_id

  default_url_redirect {
    https_redirect         = true
    redirect_response_code = "MOVED_PERMANENTLY_DEFAULT"
    strip_query            = false
  }
}

resource "google_compute_target_http_proxy" "http_redirect" {
  count = var.enabled && var.enable_http_redirect ? 1 : 0

  name    = "${var.name_prefix}-http-redirect"
  project = var.project_id
  url_map = google_compute_url_map.http_redirect[0].self_link
}

resource "google_compute_global_forwarding_rule" "http_redirect" {
  count = var.enabled && var.enable_http_redirect ? 1 : 0

  name                  = "${var.name_prefix}-http"
  project               = var.project_id
  ip_address            = google_compute_global_address.default[0].address
  ip_protocol           = "TCP"
  load_balancing_scheme = "EXTERNAL_MANAGED"
  port_range            = "80"
  target                = google_compute_target_http_proxy.http_redirect[0].self_link
}

resource "google_dns_record_set" "a" {
  count = local.create_dns_record ? 1 : 0

  name         = var.dns_record_name
  project      = var.project_id
  managed_zone = var.dns_managed_zone
  type         = "A"
  ttl          = 300
  rrdatas      = [google_compute_global_address.default[0].address]
}
