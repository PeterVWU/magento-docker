# ECOM-7 Core-First Import Plan

## Decision

The first migration rehearsal imports only data that the current clean
`magento-modern` build can understand:

- catalog products and configurable relations;
- customer accounts and addresses;
- orders, order items, addresses, payments, history, invoices, and shipments;
- category links and inventory rows;
- media references without media binaries.

Retained extension behavior is intentionally deferred until those modules are
installed in the fresh build:

- store credit;
- reward points;
- sales rep;
- order source;
- customer licenses;
- payment request behavior.

This is a sequencing choice, not a keep/remove decision.

## Current Target State

The clean local target currently has Magento core modules plus
`Vapewholesaleusa_GcsRemoteStorage`. The retained third-party and custom module
stack from ECOM-20 has not been installed yet, so the first run must not assume
those tables or business rules exist in the target schema.

## Import Order

1. Foundation metadata required by the slice:
   - websites, stores, attribute sets, attributes, options, categories, and
     customer groups referenced by the selected rows.
2. Catalog:
   - products;
   - product EAV values;
   - configurable parent links;
   - category-product links;
   - inventory rows;
   - media references.
3. Customers:
   - customer entities;
   - customer EAV values;
   - customer addresses.
4. Orders:
   - order headers;
   - order items;
   - order addresses;
   - payments;
   - status history;
   - invoices and invoice items;
   - shipments and shipment items.
5. Reindex and validate:
   - catalog/admin loads;
   - customer/admin loads;
   - order/admin loads;
   - GraphQL catalog/customer checks that are supported by the current core
     target.

## Known Gaps For Run 1

- The GCS bucket is only the target media store. There is no source media export
  yet, so product/category/customer-license files cannot be copied during this
  run.
- Product media references can be imported, but image rendering will remain a
  known validation gap until source files are available.
- Extension-specific data remains outside the first import because the target
  modules are not installed yet.
- Payment-request behavior has no standalone source table in the legacy code
  reviewed so far; it will be validated later against migrated order/invoice
  state once the module is installed.

## Immediate Next Work

Foundation metadata is now seeded for the first slice, and the rehearsal does
require explicit remaps; see
[foundation-metadata-findings.md](foundation-metadata-findings.md) and
[metadata-map-plan.md](metadata-map-plan.md).

The next step is to build the first core-data load transform:

- compare source and target table shapes for the in-scope core tables;
- preserve source entity IDs only where the target range is demonstrably clear;
- rewrite metadata foreign keys through the seeded maps;
- load catalog first, then customers, then sales records in dependency order.
