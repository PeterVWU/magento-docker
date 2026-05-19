output "media_bucket_name" {
  value = google_storage_bucket.media.name
}
output "assets_bucket_name" {
  value = google_storage_bucket.assets.name
}
output "opensearch_snapshot_bucket_name" {
  value = google_storage_bucket.opensearch_snapshots.name
}
output "media_bucket_url" {
  value = google_storage_bucket.media.url
}
output "assets_bucket_url" {
  value = google_storage_bucket.assets.url
}
output "opensearch_snapshot_bucket_url" {
  value = google_storage_bucket.opensearch_snapshots.url
}
