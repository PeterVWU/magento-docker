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
- Production DB evidence is now captured in `setup-module.csv`, `table-presence.csv`, and `config-paths.csv`.
- Private Composer credentials are present in legacy `composer.json` repository URLs for Mirasvit and Firebear. The CSV records only sanitized risk notes. Clean-room secret handling belongs to ECOM-59 before any approved dependency set is installed.

## Owner Decision Rule

The practical ECOM-20 decision is captured in `legacy-local-modules.csv` as `owner_decision`:

| Owner Decision | Meaning |
| --- | --- |
| Keep | Include this module in the new Magento build as-is for now. |
| Remove | Do not include this module in the new Magento build. |

The generated `initial_decision` column remains as historical scan context only. It does not override `owner_decision`.

## First-Pass Defaults

| Category | Initial Decision | Source | Notes |
| --- | --- | --- | --- |
| `WeltPixel/*` local modules | Remove | ECOM-20 ticket; legacy repo scan | Theme/storefront modules are removed unless later justified for admin, email, PDF, or backend API behavior. |
| `Vapewholesaleusa/WeltPixel*` wrappers | Remove | ECOM-20 ticket; legacy repo scan | Treat as Pearl/WeltPixel legacy storefront surface until proven otherwise. |
| HubSpot, Zoho, QuickBooks, Odoo, CED POS, Instagram feed | Remove | ECOM-20 ticket; legacy repo scan; legacy composer.json | Known dead integrations and related cron/table surfaces should not be installed. |
| Payment, order, inventory, customer, shipping, tax, catalog, PDF, admin utility modules | Defer or Rewrite Candidate | ECOM-20 ticket; legacy repo scan | Keep-category candidates need owner, source, enabled-state, DB, cron, observer/plugin/preference, headless/API, license, and test review. |
| ECOM-55 high-risk overrides | Rewrite Candidate | ECOM-55 ticket; legacy repo scan | Includes payment, stock deduction, exports, split DB, core sales/catalog/configurable overrides, PDF/email/admin utilities, and Rootways AuthorizeCIM overrides named by ECOM-55. |

## Known Keep-Category Context

These groups explain why many modules were marked for owner review before `owner_decision` was added:

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

## Known Remove Context

| Workstream | Removal Basis | Follow-Up |
| --- | --- | --- |
| WeltPixel/Pearl theme surface | ECOM-20 says exclude unless explicitly required for backend/admin behavior. | Verify no email/PDF/admin/API dependency before final removal. |
| HubSpot, Zoho, QuickBooks, Odoo, CED POS, Instagram feed | ECOM-20 known remove list. | Confirm no active production integration owner or pending migration dependency. |
| Dealer/distributor permission modules | ECOM-20 known remove list. | Confirm related DB tables/config are no longer used. |
| Legacy frontend performance/theme tooling | Headless storefront should own frontend optimization. | Replace only if backend behavior remains necessary. |

## Matrix Columns

The main decision column is:

| Column | Required Evidence |
| --- | --- |
| `owner_decision` | Owner-marked decision in `legacy-local-modules.csv`; current values are `Keep` or `Remove`. |

Supporting columns are retained for traceability:

| Column | Purpose |
| --- | --- |
| `initial_decision` | Generated first-pass scan context. |
| `source` | Evidence source used during the generated scan. |
| `notes` | Generated scan notes. |
| `confirmation_needed` | Historical checklist from the first-pass audit. |

## Immediate Blockers

- Local module keep/remove decisions are recorded.
- Composer package inclusion still needs to follow the kept local modules and ECOM-59 credential handling for private repositories.
