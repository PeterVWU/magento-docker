output "emails" {
  value = { for k, v in google_service_account.accounts : k => v.email }
}

output "members" {
  value = { for k, v in google_service_account.accounts : k => v.member }
}

output "ids" {
  value = { for k, v in google_service_account.accounts : k => v.id }
}
