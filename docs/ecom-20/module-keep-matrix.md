# ECOM-20 Module Keep Matrix and Dependency Audit

## Scope

This is the first-pass ECOM-20 audit scaffold for the fresh `magento-modern` repository. The legacy `vusa244` tree is used strictly as reference material for inventory and classification; no legacy code, legacy Composer credentials, patches, `composer.lock`, `auth.json`, `.env`, `env.php`, database dumps, or media are copied into this repository as part of this work.

## Source Artifacts

| Artifact | Purpose | Source |
| --- | --- | --- |
| [legacy-local-modules.csv](legacy-local-modules.csv) | Generated inventory of `vusa244/app/code` local modules with first-pass decisions. | `legacy repo scan`, `ECOM-20 ticket`, `ECOM-55 ticket`, `needs production config/DB confirmation` |
| [legacy-composer-requirements.csv](legacy-composer-requirements.csv) | Generated inventory of legacy `composer.json` requirements with sanitized credential-risk notes. | `legacy composer.json`, `ECOM-20 ticket`, `needs production config/DB confirmation` |
| [production-modules.csv](production-modules.csv) | Sanitized production module enablement exported from production `app/etc/config.php`. | `production app/etc/config.php` |
| [setup-module.csv](setup-module.csv) | Sanitized production `setup_module` versions for installed module history. | `sanitized production DB` |
| [table-presence.csv](table-presence.csv) | Sanitized table-presence evidence for known remove-risk integration table patterns. | `sanitized production DB table check` |
| [config-paths.csv](config-paths.csv) | Sanitized `core_config_data` scope/path evidence without values. | `sanitized production DB config path check` |

## Current Evidence Gaps

- The legacy `vusa244` repo does not contain a committed `app/etc/config.php`; production module enablement is now captured in `production-modules.csv`.
- Production DB evidence is now captured in `setup-module.csv`, `table-presence.csv`, and `config-paths.csv`, but final decisions still need row-level reconciliation back to each local module and Composer package.
- Owner-confirmed business inventory was not present in `.omc/autopilot/full-inventory.md` in the recloned legacy tree.
- Private Composer credentials are present in legacy `composer.json` repository URLs for Mirasvit and Firebear. The CSV records only sanitized risk notes. Clean-room secret handling belongs to ECOM-59 before any approved dependency set is installed.

## Decision States

| State | Meaning |
| --- | --- |
| Keep | Approved to retain as-is after owner, license, source, API, and test review. |
| Replace | Business capability remains, but the implementation should move to a different package or platform service. |
| Rewrite | Business capability remains, but the legacy module should not be carried forward directly. |
| Rewrite Candidate | First-pass high-risk marker, especially for core/vendor overrides named by ECOM-55. |
| Remove | First-pass removal candidate from dead integrations, theme-only modules, or headless-incompatible storefront behavior. |
| Defer | Inventory exists, but owner/source/config/API evidence is insufficient for a final decision. |

## First-Pass Defaults

| Category | Initial Decision | Source | Notes |
| --- | --- | --- | --- |
| `WeltPixel/*` local modules | Remove | ECOM-20 ticket; legacy repo scan | Theme/storefront modules are removed unless later justified for admin, email, PDF, or backend API behavior. |
| `Vapewholesaleusa/WeltPixel*` wrappers | Remove | ECOM-20 ticket; legacy repo scan | Treat as Pearl/WeltPixel legacy storefront surface until proven otherwise. |
| HubSpot, Zoho, QuickBooks, Odoo, CED POS, Instagram feed | Remove | ECOM-20 ticket; legacy repo scan; legacy composer.json | Known dead integrations and related cron/table surfaces should not be installed. |
| Payment, order, inventory, customer, shipping, tax, catalog, PDF, admin utility modules | Defer or Rewrite Candidate | ECOM-20 ticket; legacy repo scan | Keep-category candidates need owner, source, enabled-state, DB, cron, observer/plugin/preference, headless/API, license, and test review. |
| ECOM-55 high-risk overrides | Rewrite Candidate | ECOM-55 ticket; legacy repo scan | Includes payment, stock deduction, exports, split DB, core sales/catalog/configurable overrides, PDF/email/admin utilities, and Rootways AuthorizeCIM overrides named by ECOM-55. |

## Known Keep-Category Workstreams

These remain `Defer` or `Rewrite Candidate` until reviewed:

| Workstream | Examples | Required Follow-Up |
| --- | --- | --- |
| Payment | Authorize.net CIM, FluidPay, Payment Request, Region Payment Restriction | Confirm payment owner, gateway support, PCI/security impact, failure paths, API coverage, and tests. |
| Order management | Order Approval, Auto-Hold, Order Source, exports, unshipped alerts, tracking email, custom sales rules | Confirm operational owner, cron/export behavior, admin workflows, file responses, and regression tests. |
| Inventory | Stock Deduction Override, Stock Queue, Stock Sheet, Product Checker, Out-of-Stock Item Remover, Quantity Rules | Confirm MSI behavior, migration impact, queue semantics, and stock edge-case tests. |
| Customer/B2B | Customer License, Group Notification, Group Pricing, Customer Attributes, Reward Points, Store Credit, Referral, Reset Password CLI | Confirm account/headless API requirements, admin workflows, email behavior, and test coverage. |
| Shipping/Tax | Admin Shipping Method, USPS Ground, State Shipping Ban, Amasty restrictions, Tax Management, ShipStation, Google Address Verification | Confirm carrier/admin behavior, tax owner, API behavior, and production config dependencies. |
| Integrations/features | Omnisend, OpenAI content, Social Login, GA4 frontend reimplementation, Cloudflare purge, Mageplaza SMTP | Confirm which integrations survive the headless architecture and which move to frontend/platform services. |
| Catalog/promotions | MageWorx APO, BSS Configurable Grid View, Firebear Import/Export, Nicotine Warning, Hide Price, Grouped Options, Search, promotions, ShopBy/Brand | Confirm backend vs storefront responsibility, API coverage, licensing, and data migration impact. |
| PDF/admin/infrastructure | Custom Invoice PDF, credit memo email, reply-to email, payment failures email, admin actions log, table/image cleanup, image optimizer, session optimization, split DB | Confirm admin-only business need, Cloud SQL compatibility, supportability, and tests. |

## Known Remove Workstreams

| Workstream | Removal Basis | Follow-Up |
| --- | --- | --- |
| WeltPixel/Pearl theme surface | ECOM-20 says exclude unless explicitly required for backend/admin behavior. | Verify no email/PDF/admin/API dependency before final removal. |
| HubSpot, Zoho, QuickBooks, Odoo, CED POS, Instagram feed | ECOM-20 known remove list. | Confirm no active production integration owner or pending migration dependency. |
| Dealer/distributor permission modules | ECOM-20 known remove list. | Confirm related DB tables/config are no longer used. |
| Legacy frontend performance/theme tooling | Headless storefront should own frontend optimization. | Replace only if backend behavior remains necessary. |

## Matrix Columns To Complete

Each final decision row should include:

| Column | Required Evidence |
| --- | --- |
| Decision | Keep, Replace, Rewrite, Remove, or Defer. |
| Business owner | Named owner or decision group. |
| Source of truth | Owner inventory, production config, DB evidence, code scan, Composer requirement, or ticket reference. |
| Feature dependency | Business process, integration, admin workflow, or storefront/headless requirement. |
| DB tables | Tables created/read/written and migration impact. |
| Cron/jobs | Cron groups, queues, scheduled jobs, external calls. |
| Observers/plugins/preferences | Especially core/vendor replacements and ObjectManager fallback. |
| Frontend/headless impact | Whether the behavior moves to frontend, API, admin-only, or disappears. |
| GraphQL/REST coverage | Existing or required API contract. |
| License/support status | Vendor access, supportability, replacement package risk. |
| Test coverage | Existing tests and required unit/integration/API coverage. |

## Immediate Blockers

- Production evidence must be reconciled into the first-pass module and Composer inventories.
- Owner-confirmed keep/remove inventory is missing.
- ECOM-59 must resolve clean repository auth and credential rotation before private Composer packages can be evaluated in the clean baseline.
