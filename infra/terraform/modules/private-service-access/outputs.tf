output "reserved_range_name" {
  value = google_compute_global_address.private_service_range.name
}

output "connection_id" {
  value = google_service_networking_connection.private_vpc_connection.id
}
