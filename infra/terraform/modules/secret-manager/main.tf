resource "google_secret_manager_secret" "secrets" {
  for_each  = var.secret_ids
  project   = var.project_id
  secret_id = each.value
  labels    = var.labels

  replication {
    auto {}
  }
}

resource "google_secret_manager_secret_iam_member" "runtime_access" {
  for_each = {
    for pair in setproduct(var.runtime_secret_ids, var.runtime_accessor_members) :
    "${pair[0]}:${pair[1]}" => { secret_id = pair[0], member = pair[1] }
  }

  project   = var.project_id
  secret_id = google_secret_manager_secret.secrets[each.value.secret_id].secret_id
  role      = "roles/secretmanager.secretAccessor"
  member    = each.value.member
}

resource "google_secret_manager_secret_iam_member" "release_access" {
  for_each = {
    for pair in setproduct(var.release_secret_ids, var.release_accessor_members) :
    "${pair[0]}:${pair[1]}" => { secret_id = pair[0], member = pair[1] }
  }

  project   = var.project_id
  secret_id = google_secret_manager_secret.secrets[each.value.secret_id].secret_id
  role      = "roles/secretmanager.secretAccessor"
  member    = each.value.member
}

resource "google_secret_manager_secret_iam_member" "build_access" {
  for_each = {
    for pair in setproduct(var.build_secret_ids, var.build_accessor_members) :
    "${pair[0]}:${pair[1]}" => { secret_id = pair[0], member = pair[1] }
  }

  project   = var.project_id
  secret_id = google_secret_manager_secret.secrets[each.value.secret_id].secret_id
  role      = "roles/secretmanager.secretAccessor"
  member    = each.value.member
}
