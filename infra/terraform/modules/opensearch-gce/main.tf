locals {
  node_names = [for i in range(var.node_count) : "${var.name}-${format("%02d", i + 1)}"]
}

resource "google_compute_disk" "data" {
  count   = var.node_count
  name    = "${var.name}-data-${format("%02d", count.index + 1)}"
  project = var.project_id
  zone    = var.zone
  type    = var.data_disk_type
  size    = var.data_disk_size_gb
  labels  = var.labels
}

resource "google_compute_instance" "node" {
  count        = var.node_count
  name         = local.node_names[count.index]
  project      = var.project_id
  zone         = var.zone
  machine_type = var.machine_type
  tags         = [var.cluster_tag]
  labels       = var.labels

  boot_disk {
    initialize_params {
      image = var.boot_image
      size  = var.boot_disk_size_gb
      type  = "pd-balanced"
    }
  }

  attached_disk {
    source      = google_compute_disk.data[count.index].id
    device_name = "opensearch-data"
  }

  network_interface {
    network    = var.network
    subnetwork = var.subnetwork
    network_ip = length(var.node_private_ips) > 0 ? var.node_private_ips[count.index] : null
  }

  service_account {
    email  = var.service_account_email
    scopes = ["https://www.googleapis.com/auth/cloud-platform"]
  }

  metadata = {
    opensearch_cluster_name = var.name
    opensearch_node_index   = tostring(count.index + 1)
    phase                   = "ecom-6-bootstrap"
  }

  metadata_startup_script = templatefile("${path.module}/startup.sh.tftpl", {
    cluster_name                  = var.name
    node_count                    = var.node_count
    node_names_json               = jsonencode(local.node_names)
    opensearch_version            = var.opensearch_version
    heap_size                     = var.heap_size
    project_id                    = var.project_id
    admin_password_secret_id      = var.admin_password_secret_id
    security_hardening_enabled    = var.security_hardening_enabled
    tls_ca_cert_secret_id         = var.tls_ca_cert_secret_id
    tls_node_cert_secret_id       = var.tls_node_cert_secret_id
    tls_node_key_secret_id        = var.tls_node_key_secret_id
    tls_admin_cert_secret_id      = var.tls_admin_cert_secret_id
    tls_admin_key_secret_id       = var.tls_admin_key_secret_id
    admin_distinguished_name      = var.admin_distinguished_name
    node_distinguished_name       = var.node_distinguished_name
    app_password_secret_id        = var.app_password_secret_id
    operator_password_secret_id   = var.operator_password_secret_id
    breakglass_password_secret_id = var.breakglass_password_secret_id
    magento_index_prefix          = var.magento_index_prefix
    snapshot_bucket_name          = var.snapshot_bucket_name
    snapshot_base_path            = var.snapshot_base_path
  })
}

resource "google_compute_firewall" "opensearch_http_ranges" {
  count = length(var.app_source_ranges) > 0 ? 1 : 0

  name          = "${var.name}-http-ranges"
  project       = var.project_id
  network       = var.network
  target_tags   = [var.cluster_tag]
  source_ranges = var.app_source_ranges

  allow {
    protocol = "tcp"
    ports    = ["9200"]
  }
}

resource "google_compute_firewall" "opensearch_http_tags" {
  count = length(var.app_source_tags) > 0 ? 1 : 0

  name        = "${var.name}-http-tags"
  project     = var.project_id
  network     = var.network
  target_tags = [var.cluster_tag]
  source_tags = var.app_source_tags

  allow {
    protocol = "tcp"
    ports    = ["9200"]
  }
}

resource "google_compute_firewall" "opensearch_transport" {
  name        = "${var.name}-transport"
  project     = var.project_id
  network     = var.network
  target_tags = [var.cluster_tag]
  source_tags = [var.cluster_tag]

  allow {
    protocol = "tcp"
    ports    = ["9300"]
  }
}
