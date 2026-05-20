locals {
  web_tag     = "${var.name_prefix}-web"
  worker_tag  = "${var.name_prefix}-worker"
  release_tag = "${var.name_prefix}-release"

  common_env = {
    MAGENTO_ENVIRONMENT                   = var.environment
    MAGENTO_IMAGE_SHA                     = var.image_sha
    MAGENTO_DB_HOST                       = var.db_host
    MAGENTO_DB_NAME                       = var.db_name
    MAGENTO_DB_USER                       = var.db_user
    MAGENTO_DB_PASSWORD_SECRET_ID         = var.db_password_secret_id
    MAGENTO_REDIS_HOST                    = var.redis_host
    MAGENTO_REDIS_PORT                    = tostring(var.redis_port)
    MAGENTO_REDIS_AUTH_SECRET_ID          = var.redis_auth_secret_id
    MAGENTO_OPENSEARCH_HOST               = var.opensearch_host
    MAGENTO_OPENSEARCH_PORT               = tostring(var.opensearch_port)
    MAGENTO_OPENSEARCH_PASSWORD_SECRET_ID = var.opensearch_password_secret_id
    MAGENTO_CRYPT_KEY_SECRET_ID           = var.crypt_key_secret_id
    MAGENTO_MEDIA_BUCKET                  = var.media_bucket_name
    MAGENTO_ASSETS_BUCKET                 = var.assets_bucket_name
  }

  web_env = merge(local.common_env, {
    MAGENTO_RUNTIME_ROLE = "web"
  })

  worker_env = merge(local.common_env, {
    MAGENTO_RUNTIME_ROLE          = "worker"
    MAGENTO_RUN_CRON              = "1"
    MAGENTO_CRON_INTERVAL         = tostring(var.cron_interval_seconds)
    MAGENTO_CONSUMERS             = join(",", var.worker_consumers)
    MAGENTO_CONSUMER_MAX_MESSAGES = tostring(var.worker_consumer_max_messages)
  })

  release_env = merge(local.common_env, {
    MAGENTO_RUNTIME_ROLE          = "release"
    MAGENTO_DB_USER               = var.db_release_user
    MAGENTO_DB_PASSWORD_SECRET_ID = var.db_release_password_secret_id
    MAGENTO_RELEASE_RUN_UPGRADE   = "1"
  })
}

resource "google_compute_health_check" "web" {
  name    = "${var.name_prefix}-web-healthz"
  project = var.project_id

  timeout_sec         = 5
  check_interval_sec  = 10
  healthy_threshold   = 2
  unhealthy_threshold = 3

  http_health_check {
    port         = 8080
    request_path = "/healthz"
  }
}

resource "google_compute_instance_template" "web" {
  name_prefix  = "${var.name_prefix}-web-"
  project      = var.project_id
  machine_type = var.web_machine_type
  tags         = [local.web_tag]
  labels       = var.labels

  disk {
    boot         = true
    auto_delete  = true
    source_image = var.boot_image
    disk_size_gb = var.boot_disk_size_gb
    disk_type    = "pd-balanced"
  }

  network_interface {
    network    = var.network
    subnetwork = var.subnetwork
  }

  service_account {
    email  = var.service_account_email
    scopes = ["https://www.googleapis.com/auth/cloud-platform"]
  }

  metadata = {
    environment          = var.environment
    magento_runtime_role = "web"
    image_sha            = var.image_sha
    phase                = "ecom-8-compute-runtime"
  }

  metadata_startup_script = templatefile("${path.module}/startup.sh.tftpl", {
    project_id                    = var.project_id
    runtime_image                 = var.runtime_image
    role                          = "web"
    env                           = local.web_env
    db_password_secret_id         = var.db_password_secret_id
    redis_auth_secret_id          = var.redis_auth_secret_id
    opensearch_password_secret_id = var.opensearch_password_secret_id
    crypt_key_secret_id           = var.crypt_key_secret_id
  })

  lifecycle {
    create_before_destroy = true
  }
}

resource "google_compute_region_instance_group_manager" "web" {
  name               = "${var.name_prefix}-web"
  project            = var.project_id
  region             = var.region
  base_instance_name = "${var.name_prefix}-web"
  target_size        = var.web_target_size

  version {
    name              = "primary"
    instance_template = google_compute_instance_template.web.id
  }

  named_port {
    name = "http"
    port = 8080
  }

  auto_healing_policies {
    health_check      = google_compute_health_check.web.id
    initial_delay_sec = 300
  }

  update_policy {
    type                         = "PROACTIVE"
    minimal_action               = "REPLACE"
    replacement_method           = "SUBSTITUTE"
    max_surge_fixed              = 3
    max_unavailable_fixed        = 0
    instance_redistribution_type = "PROACTIVE"
  }
}

resource "google_compute_instance_template" "worker" {
  name_prefix  = "${var.name_prefix}-worker-"
  project      = var.project_id
  machine_type = var.worker_machine_type
  tags         = [local.worker_tag]
  labels       = var.labels

  disk {
    boot         = true
    auto_delete  = true
    source_image = var.boot_image
    disk_size_gb = var.boot_disk_size_gb
    disk_type    = "pd-balanced"
  }

  network_interface {
    network    = var.network
    subnetwork = var.subnetwork
  }

  service_account {
    email  = var.service_account_email
    scopes = ["https://www.googleapis.com/auth/cloud-platform"]
  }

  metadata = {
    environment          = var.environment
    magento_runtime_role = "worker"
    image_sha            = var.image_sha
    phase                = "ecom-8-compute-runtime"
  }

  metadata_startup_script = templatefile("${path.module}/startup.sh.tftpl", {
    project_id                    = var.project_id
    runtime_image                 = var.runtime_image
    role                          = "worker"
    env                           = local.worker_env
    db_password_secret_id         = var.db_password_secret_id
    redis_auth_secret_id          = var.redis_auth_secret_id
    opensearch_password_secret_id = var.opensearch_password_secret_id
    crypt_key_secret_id           = var.crypt_key_secret_id
  })

  lifecycle {
    create_before_destroy = true
  }
}

resource "google_compute_instance_template" "release" {
  name_prefix  = "${var.name_prefix}-release-"
  project      = var.project_id
  machine_type = var.worker_machine_type
  tags         = [local.release_tag]
  labels       = var.labels

  disk {
    boot         = true
    auto_delete  = true
    source_image = var.boot_image
    disk_size_gb = var.boot_disk_size_gb
    disk_type    = "pd-balanced"
  }

  network_interface {
    network    = var.network
    subnetwork = var.subnetwork
  }

  service_account {
    email  = var.release_service_account_email
    scopes = ["https://www.googleapis.com/auth/cloud-platform"]
  }

  metadata = {
    environment          = var.environment
    magento_runtime_role = "release"
    image_sha            = var.image_sha
    phase                = "ecom-8-compute-runtime"
  }

  metadata_startup_script = templatefile("${path.module}/startup.sh.tftpl", {
    project_id                    = var.project_id
    runtime_image                 = var.runtime_image
    role                          = "release"
    env                           = local.release_env
    db_password_secret_id         = var.db_release_password_secret_id
    redis_auth_secret_id          = var.redis_auth_secret_id
    opensearch_password_secret_id = var.opensearch_password_secret_id
    crypt_key_secret_id           = var.crypt_key_secret_id
  })

  lifecycle {
    create_before_destroy = true
  }
}

resource "google_compute_instance_group_manager" "worker" {
  name               = "${var.name_prefix}-worker"
  project            = var.project_id
  zone               = var.zone
  base_instance_name = "${var.name_prefix}-worker"
  target_size        = var.worker_target_size

  version {
    name              = "primary"
    instance_template = google_compute_instance_template.worker.id
  }

  update_policy {
    type                  = "PROACTIVE"
    minimal_action        = "REPLACE"
    replacement_method    = "SUBSTITUTE"
    max_surge_fixed       = 1
    max_unavailable_fixed = 0
  }
}

resource "google_compute_firewall" "web_http_ranges" {
  count = length(var.web_source_ranges) > 0 ? 1 : 0

  name          = "${var.name_prefix}-web-http-ranges"
  project       = var.project_id
  network       = var.network
  target_tags   = [local.web_tag]
  source_ranges = var.web_source_ranges

  allow {
    protocol = "tcp"
    ports    = ["8080"]
  }
}

resource "google_compute_firewall" "web_http_tags" {
  count = length(var.web_source_tags) > 0 ? 1 : 0

  name        = "${var.name_prefix}-web-http-tags"
  project     = var.project_id
  network     = var.network
  target_tags = [local.web_tag]
  source_tags = var.web_source_tags

  allow {
    protocol = "tcp"
    ports    = ["8080"]
  }
}
