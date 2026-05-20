output "web_instance_group" {
  value = google_compute_region_instance_group_manager.web.instance_group
}

output "web_instance_template" {
  value = google_compute_instance_template.web.self_link
}

output "worker_instance_group" {
  value = google_compute_instance_group_manager.worker.instance_group
}

output "worker_instance_template" {
  value = google_compute_instance_template.worker.self_link
}

output "release_instance_template" {
  value = google_compute_instance_template.release.self_link
}

output "web_health_check" {
  value = google_compute_health_check.web.self_link
}

output "runtime_tags" {
  value = {
    web     = local.web_tag
    worker  = local.worker_tag
    release = local.release_tag
  }
}
