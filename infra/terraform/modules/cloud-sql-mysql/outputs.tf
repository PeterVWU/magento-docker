output "instance_name" {
  value = google_sql_database_instance.mysql.name
}
output "connection_name" {
  value = google_sql_database_instance.mysql.connection_name
}
output "private_ip_address" {
  value = google_sql_database_instance.mysql.private_ip_address
}
output "database_name" {
  value = google_sql_database.magento.name
}
output "app_user_name" {
  value = google_sql_user.app.name
}
output "release_user_name" {
  value = google_sql_user.release.name
}
