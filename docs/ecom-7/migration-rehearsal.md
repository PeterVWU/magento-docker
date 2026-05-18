# ECOM-7 Data Cleaning and Migration Rehearsal

## Goal

Rehearse a selective migration from the legacy Magento estate into the fresh
`magento-modern` project before staging is treated as production-like.

This phase proves the data path locally first. It does not provision managed
services, replace ECOM-55 module rewrite work, or copy raw production dumps into
this repository.

## Inputs and Dependencies

| Input | Current Source |
| --- | --- |
| Kept module/package decisions | [ECOM-20 module keep matrix](../ecom-20/module-keep-matrix.md) |
| Runtime and media target architecture | [Runtime contract](../architecture/runtime-contract.md) |
| GCS media adapter behavior already validated locally | [ECOM-60 GCS adapter validation](../ecom-60/gcs-media-adapter-validation.md) |
| Critical custom module rewrite decisions | ECOM-55, which blocks final ECOM-7 completion |

ECOM-7 can start before ECOM-55 is complete, but the final rehearsal dataset and
the final extension-data import list cannot be frozen until ECOM-55 resolves the
business-critical custom module surface.

## Rehearsal Principles

- Prefer sanitized rehearsal data. If a production-derived slice with real PII is
  explicitly approved for a local rehearsal, keep every derived artifact local,
  ignored, access-restricted, and out of Git, tickets, screenshots, and shared
  logs.
- Keep raw production dumps, exports, media, credentials, and secrets out of Git
  and out of the Docker build context.
- Migrate only data needed by retained business behavior.
- Rebuild transient Magento state instead of importing it.
- Record every dataset, transform, import, and validation step so the rehearsal
  can be repeated without relying on memory.
- Prefer a small representative slice first, then expand once the workflow is
  stable.

## Local Artifact Layout

Sanitized runtime artifacts stay ignored under `var/import/sanitized/` after
local setup:

```text
var/import/sanitized/
  manifest.csv
  source/
  cleaned/
  media/
  reports/
```

The committed manifest template lives at
[rehearsal-dataset-manifest.example.csv](rehearsal-dataset-manifest.example.csv).
Copy it into the ignored runtime directory for each rehearsal run and fill it in
with the actual dataset names, owners, commands, and validation results.

If the approved rehearsal uses real production fields, keep the working copy in
an ignored local path outside the repository instead of under `var/import/`.
That data is still valid rehearsal input, but it is not sanitized and should not
be described as such.

## Migration Policy

### Migrate

| Domain | Minimum Local Rehearsal Scope |
| --- | --- |
| Products | Configurable and simple products, attributes, categories, prices, stock, product media relationships. |
| Customers | Customer groups, B2B/customer attributes, addresses, license-related data, and required account fields after sanitization. |
| Orders | Order headers, items, invoices, shipments, payments, status history, and retained custom order fields. |
| Extension data | Only retained business-critical data confirmed by ECOM-20 and ECOM-55, such as store credit, rewards, sales rep, order source, tax rules, customer licenses, and payment request data where still required. |
| Media | Product, category, and customer-license media required by migrated records. |

### Rebuild or Exclude

| Domain | Policy |
| --- | --- |
| Indexes and search indexes | Rebuild from migrated canonical data. |
| Changelog tables | Exclude. |
| Cache, sessions, locks | Exclude. |
| Reports, logs, cron history | Exclude unless a specific business retention requirement is documented. |
| Dead integration tables | Exclude when tied to modules removed by ECOM-20. |
| Raw secrets, tokens, payment credentials | Exclude and replace with safe local configuration. |

## First Representative Slice

The first rehearsal dataset should be small enough to debug quickly but broad
enough to expose real migration risk:

| Area | Initial Slice |
| --- | --- |
| Catalog | 10 configurable products with children, mixed attribute sets, categories, stock states, and product images. |
| Customers | 25 customers across the required customer groups, including B2B/customer attributes and sanitized addresses. |
| Orders | 50 orders spanning completed, processing, canceled, refunded, invoiced, and shipped states. |
| Extension data | At least one retained example per extension-data family that survives ECOM-20/ECOM-55. |
| Media | Product images, category media, and at least one customer-license media path if that feature survives. |

If the first slice cannot represent a required domain without unsafe data, record
the gap in the manifest and create a follow-up ticket instead of widening the
dataset informally.

For the first production-derived rehearsal requested on 2026-05-15, use records
from the month preceding the dump date and keep these extension families in
scope: store credit, rewards, sales rep, order source, customer licenses, and
payment request data.

Initial source profiling against the 2026-04-03 production dump found:

| Window | Orders | Customers with orders | Store-credit orders | Reward-point orders | Sales-rep orders | Order-source rows |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| `2026-03-03 <= created_at < 2026-04-03` | 13,563 | 11,191 | 1,036 | 53 | 426 | 0 |

`OrderSource` remains a retained requirement, but the first dump profile found
no rows to migrate from `vapewholesaleusa_ordersource_ordersource`.

The first deterministic 50-order rehearsal sample currently expands to:

| Domain | Rows |
| --- | ---: |
| Orders | 50 |
| Customers | 47 |
| Order addresses | 100 |
| Customer addresses | 193 |
| Order items | 585 |
| Order-item products | 472 |
| Products including configurable parents | 601 |
| Configurable parent links | 472 |
| Sales-rep rows | 11 |
| Order-source rows | 0 |

The selected orders currently contain only simple order-item rows. The dataset
assembly therefore expands the product set through `catalog_product_super_link`
so the matching configurable parent products are included for later migration
rehearsal.

The committed export contract is defined in
[export-domain-files.sql](export-domain-files.sql). Real production-derived
TSV files are generated locally with `scripts/ecom7/export-domain-files` under
the ignored runtime path `var/import/production-derived/ecom7-first-slice/`.

The current TSV export set is only the first domain boundary. Before the first
meaningful Magento import attempt, the rehearsal also needs the additional
source tables in [next-required-tables.txt](next-required-tables.txt), including
product/customer EAV values, product media, inventory, and order payment/invoice/
shipment/history children.

After loading those additional source tables, the same 50-order rehearsal sample
currently expands to:

| Domain | Rows |
| --- | ---: |
| Product datetime EAV | 2,121 |
| Product decimal EAV | 1,974 |
| Product int EAV | 23,002 |
| Product text EAV | 2,617 |
| Product varchar EAV | 12,926 |
| Customer datetime EAV | 2 |
| Customer decimal EAV | 0 |
| Customer int EAV | 325 |
| Customer text EAV | 6 |
| Customer varchar EAV | 79 |
| Customer-address EAV across loaded value tables | 0 |
| Category-product links | 1,466 |
| Product media-gallery entity links | 1,741 |
| Order payments | 50 |
| Order status history rows | 93 |
| Invoices | 40 |
| Invoice items | 361 |
| Shipments | 25 |
| Shipment items | 124 |
| Legacy stock items | 601 |
| MSI source items | 594 |

The first import attempt therefore needs to handle both direct entity rows and
the larger EAV/transactional closure around them. Customer-address EAV rows are
currently absent for this slice, so address validation should rely on the main
`customer_address_entity` rows unless later profiling finds address attributes
outside this sample.

The first real import rehearsal will use the
[core-first import plan](core-first-import-plan.md): migrate core Magento data
that the clean target can currently understand, then add retained extension data
after those modules exist in `magento-modern`.

The foundation metadata pass found that legacy IDs cannot be preserved safely
without a remap/seed step; see
[foundation-metadata-findings.md](foundation-metadata-findings.md).

The initial selective source import starts with the tables listed in
[initial-source-tables.txt](initial-source-tables.txt). `CustomerLicense`
currently appears to be customer-attribute data, and `PaymentRequest` currently
appears to layer behavior onto core order/invoice data rather than define its
own table, so both remain in scope even though they do not add a table to that
first extraction list. The list is intentionally split into:

1. a small selection set used to choose recent representative orders and
   customers; and
2. a second extraction set used only after those IDs are known, so historical
   line-item data is not imported just to choose the slice.

## Rehearsal Flow

1. Confirm the module/data scope against ECOM-20 and the current ECOM-55
   decisions.
2. Prepare a sanitized source slice outside Git.
3. Fill in `manifest.csv` from the committed template.
4. Export source data by domain.
5. Run cleaning checks before import:
   - duplicate SKUs;
   - orphaned attributes/options;
   - invalid encodings;
   - broken category or media references;
   - customers missing required group/attribute data;
   - orders referencing missing customers, products, payment records, or status
     history;
   - extension rows tied to modules that are no longer retained.
6. Load cleaned data into a fresh local Magento install.
7. Seed media through the chosen remote-storage path once the media portion of
   the run is ready for validation.
8. Reindex, clear targeted caches, and run validation.
9. Save reconciliation reports and blockers under the ignored rehearsal
   directory.
10. Convert unresolved migration blockers into explicit follow-up tickets before
    staging work depends on the dataset.

## Source Import Notes

- The production dump was created by MySQL 8.0.43.
- A direct full import into the local MySQL 8.4 container failed on a legacy
  foreign key that references a non-unique key before useful selection work
  began.
- The local rehearsal workflow therefore starts with a selective source import
  instead of loading every historical table. This avoids large excluded tables
  such as audit logs and reduces the amount of production-derived data handled
  locally.
- If a future full legacy import is required for analysis, it needs an explicit
  compatibility decision rather than silently changing the clean MySQL 8.4
  baseline.

## Validation Checklist

| Area | Required Result |
| --- | --- |
| Catalog admin | Products load with expected attributes, categories, prices, stock, and media. |
| Customer admin | Customers load with expected groups, addresses, and retained custom attributes. |
| Order admin | Orders, invoices, shipments, payments, and history load without broken references. |
| Storefront/API | GraphQL can read the catalog, customer/account, and order-facing data needed by the headless frontend contract. |
| Media | Product/category/license media paths resolve through the selected remote-storage path. |
| Reindex | Required indexers complete after import. |
| Exclusions | Known transient/bloat tables are absent or intentionally trimmed according to policy. |
| Reconciliation | Source/imported counts and exception reports are captured per domain. |

## Open Decisions Before Automation

- Which sanitized export format is the source of truth for each domain.
- Which ECOM-55 rewrite candidates still require legacy data migration.
- Which retained third-party modules expose their own supported import path
  versus requiring SQL-level migration or replacement logic, especially the
  exact production tables used by store credit and rewards.
- Whether customer-license media remains part of the retained data contract.
- What GraphQL queries ECOM-56 will use as the acceptance probe for migrated
  data.

## Definition of Done

ECOM-7 is complete when:

- the runbook is executable from a fresh local install;
- a representative sanitized production slice imports successfully;
- excluded table families are documented and enforced;
- admin and GraphQL validation pass for migrated data;
- media seeding is validated for the retained paths;
- unresolved blockers have follow-up tickets before ECOM-4 staging deployment.
