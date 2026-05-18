# ECOM-7 First Rehearsal Load Status

## Completed

The first core-only rehearsal slice is now loaded into the clean local target.

| Domain | Loaded target rows |
| --- | ---: |
| Products | 601 |
| Product integer EAV rows | 23,002 |
| Configurable links | 472 |
| Category-product links | 1,466 |
| Stock items | 601 |
| MSI source items | 594 |
| Customers | 47 |
| Customer addresses | 193 |
| Orders | 50 |
| Order items | 585 |
| Order addresses | 100 |
| Payments | 50 |
| Order history rows | 93 |
| Invoices | 40 |
| Invoice items | 361 |
| Shipments | 25 |
| Shipment items | 124 |

Source-versus-target reconciliation matches for the loaded sample domains.

## Notable Findings During Load

- Product attribute remapping must avoid in-place ID collisions. The loader now
  rewrites through a temporary offset before settling on target attribute IDs.
- Configurable products require more than `catalog_product_super_link`; the
  rehearsal also needs `catalog_product_relation`,
  `catalog_product_super_attribute`, and
  `catalog_product_super_attribute_label` for the Admin variation matrix.
- Catalog EAV values require a third storefront, `vapeguysinc`, even though the
  sampled customers and orders only referenced the first two storefronts.
- The clean target uses MSI while the legacy source does not expose equivalent
  stock-channel tables. Seeded target websites therefore need explicit
  `inventory_stock_sales_channel` links to `Default Stock`.
- Direct SQL sales inserts bypass Magento model hooks, so the derived sales
  grids must be refreshed after load before orders appear in Admin.
- A full Magento reindex succeeds after those stock-channel links are seeded.

## Deferred By Design

The first run still excludes extension-backed data that requires the retained
module stack:

- store credit;
- rewards;
- sales rep;
- order source;
- customer licenses;
- payment-request-specific behavior.

Those remain required for the final migration, but are intentionally not part of
the core-only load.

## Next Step

Validate loaded behavior through application-facing checks, then prepare the
module-aware follow-up pass for the retained extension data.
