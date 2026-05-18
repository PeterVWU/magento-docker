# ECOM-7 Final Validation

## Completed Checks

The first production-derived rehearsal slice now validates across the core
surfaces in the fresh local target:

| Surface | Result |
| --- | --- |
| Admin products | Simple and configurable products open correctly. |
| Admin customers | Imported customer records open correctly. |
| Admin orders | Orders render with linked customers, invoices, and shipments. |
| Catalog GraphQL | Imported configurable product resolves through `products`. |
| Customer GraphQL | Imported customer resolves through authenticated `customer`. |
| Reindexing | Magento indexers complete successfully. |

## Validation Fixes Discovered

- Added configurable support rows:
  - `catalog_product_relation`
  - `catalog_product_super_attribute`
  - `catalog_product_super_attribute_label`
- Added product storefront assignments:
  - `catalog_product_website`
- Refreshed derived sales grids after direct SQL order loads.
- Seeded missing target MSI website-to-stock links for seeded websites.

## Local-Only Customer Auth Note

Authenticated customer GraphQL was validated by setting a temporary local-only
password on one imported rehearsal customer. This is a validation mutation in
the local target only; it is not part of the migration payload.

## Deferred Follow-ups

- Retained extension-backed data migration moved to `ECOM-77`.
- Source media export and migrated GCS media-path validation moved to the media
  follow-up ticket because the source media files were not available for this
  rehearsal run.
