output "node_names" {
  value = google_compute_instance.node[*].name
}

output "node_private_ips" {
  value = google_compute_instance.node[*].network_interface[0].network_ip
}

output "cluster_tag" {
  value = var.cluster_tag
}

output "snapshot_bucket_name" {
  value = var.snapshot_bucket_name
}
