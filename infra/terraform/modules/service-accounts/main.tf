locals {
  accounts = {
    runtime = {
      account_id   = "${var.prefix}-runtime"
      display_name = "Magento ${var.environment} runtime"
      description  = "Web and worker runtime identity for Magento ${var.environment}."
    }
    release = {
      account_id   = "${var.prefix}-release"
      display_name = "Magento ${var.environment} release"
      description  = "One-off release/migration identity for Magento ${var.environment}."
    }
    opensearch = {
      account_id   = "${var.prefix}-opensearch"
      display_name = "Magento ${var.environment} OpenSearch"
      description  = "Self-managed OpenSearch node identity for Magento ${var.environment}."
    }
  }
}

resource "google_service_account" "accounts" {
  for_each = local.accounts

  project      = var.project_id
  account_id   = each.value.account_id
  display_name = each.value.display_name
  description  = each.value.description
}
