# ECOM-7 Core Load Shape Findings

## Result

The first core-data load cannot safely use `SELECT *` from the legacy source
tables.

Most core tables match, but several source tables carry retained extension
columns that are not present in the clean target yet:

- `catalog_product_entity`: sales-rep/search/Zoho fields;
- `customer_entity`: contact/marketing/Zoho fields;
- `sales_order`, `sales_order_item`, `sales_invoice`, and
  `sales_invoice_item`: store-credit, rewards, sales/marketing, and sync
  fields;
- `sales_order_payment`: token/surcharge fields.

The clean target also has two target-only `sales_order` columns:

- `dispute_status`
- `paypal_ipn_customer_notified`

## Decision For Run 1

Use a shared-column load for the core-first rehearsal:

1. insert only columns that exist in both source and target;
2. rewrite metadata foreign keys through the seeded ECOM-7 maps;
3. let target-only core columns use target defaults;
4. keep extension-only source columns deferred until the retained modules are
   installed in the clean build.

This matches the earlier sequencing decision: store credit, rewards, sales rep,
order source, customer licenses, and payment-request-related data remain
required for the final system, but their module-backed columns are not loaded in
the first core-only rehearsal.

## ID Strategy

For this first local slice only, source entity IDs are currently collision-free
against the clean target ranges:

| Domain | Sample source range | Existing target rows |
| --- | --- | ---: |
| Products | `2390..72004` | 1 |
| Customers | `1080..285843` | 0 |
| Orders | `395727..397218` | 0 |

Preserving those entity IDs is acceptable for the first rehearsal slice after
the explicit preflight check above, but metadata IDs still require maps because
their meanings differ between source and target.

## Next Work

Build the first shared-column loader in dependency order:

1. catalog entities, EAV rows, configurable links, category links, media
   references, and inventory;
2. customers and addresses;
3. orders, items, addresses, payments, history, invoices, and shipments.
