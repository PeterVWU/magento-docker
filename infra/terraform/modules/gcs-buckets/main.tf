resource "google_storage_bucket" "media" {
  name                        = var.media_bucket_name
  project                     = var.project_id
  location                    = var.location
  force_destroy               = var.force_destroy
  uniform_bucket_level_access = true
  labels                      = var.labels

  versioning {
    enabled = false
  }

  soft_delete_policy {
    retention_duration_seconds = var.soft_delete_retention_seconds
  }
}

resource "google_storage_bucket" "assets" {
  name                        = var.assets_bucket_name
  project                     = var.project_id
  location                    = var.location
  force_destroy               = var.force_destroy
  uniform_bucket_level_access = true
  labels                      = var.labels

  soft_delete_policy {
    retention_duration_seconds = var.soft_delete_retention_seconds
  }
}

resource "google_storage_bucket" "opensearch_snapshots" {
  name                        = var.opensearch_snapshot_bucket_name
  project                     = var.project_id
  location                    = var.location
  force_destroy               = var.force_destroy
  uniform_bucket_level_access = true
  labels                      = var.labels

  soft_delete_policy {
    retention_duration_seconds = var.soft_delete_retention_seconds
  }
}

resource "google_storage_bucket_iam_member" "media_object_admin" {
  for_each = var.media_object_admin_members

  bucket = google_storage_bucket.media.name
  role   = "roles/storage.objectAdmin"
  member = each.value
}

resource "google_storage_bucket_iam_member" "assets_object_admin" {
  for_each = var.assets_object_admin_members

  bucket = google_storage_bucket.assets.name
  role   = "roles/storage.objectAdmin"
  member = each.value
}

resource "google_storage_bucket_iam_member" "assets_object_viewer" {
  for_each = var.assets_object_viewer_members

  bucket = google_storage_bucket.assets.name
  role   = "roles/storage.objectViewer"
  member = each.value
}

resource "google_storage_bucket_iam_member" "opensearch_snapshot_object_admin" {
  for_each = var.opensearch_snapshot_object_admin_members

  bucket = google_storage_bucket.opensearch_snapshots.name
  role   = "roles/storage.objectAdmin"
  member = each.value
}
