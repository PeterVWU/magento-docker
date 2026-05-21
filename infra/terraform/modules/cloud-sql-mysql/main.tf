resource "google_sql_database_instance" "mysql" {
  name                = var.name
  project             = var.project_id
  region              = var.region
  database_version    = var.database_version
  deletion_protection = var.deletion_protection

  settings {
    tier              = var.tier
    edition           = var.edition
    availability_type = var.availability_type
    disk_type         = var.disk_type
    disk_size         = var.disk_size_gb
    disk_autoresize   = true
    user_labels       = var.labels

    ip_configuration {
      ipv4_enabled    = false
      private_network = var.network_self_link
    }

    backup_configuration {
      enabled            = true
      binary_log_enabled = true
      start_time         = var.backup_start_time

      backup_retention_settings {
        retained_backups = var.retained_backups
      }
    }

    maintenance_window {
      day  = var.maintenance_day
      hour = var.maintenance_hour
    }

    dynamic "database_flags" {
      for_each = var.database_flags

      content {
        name  = database_flags.key
        value = database_flags.value
      }
    }

    insights_config {
      query_insights_enabled  = true
      record_application_tags = true
      record_client_address   = true
    }
  }
}

resource "google_sql_database" "magento" {
  name     = var.database_name
  project  = var.project_id
  instance = google_sql_database_instance.mysql.name
}

resource "google_sql_user" "app" {
  name     = var.app_user_name
  project  = var.project_id
  instance = google_sql_database_instance.mysql.name
  password = var.app_user_password
}

resource "google_sql_user" "release" {
  name     = var.release_user_name
  project  = var.project_id
  instance = google_sql_database_instance.mysql.name
  password = var.release_user_password
}
