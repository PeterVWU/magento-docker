# ECOM-77 Extension-Backed Data Migration Plan

## Current State

`ECOM-55` is canceled, so it is not an active blocker for this migration pass.
The retained extension-data import should instead be gated by the concrete
target module/schema decision for each data family.

`magento-modern` currently has no retained extension modules installed under
`app/code` except `Vapewholesaleusa_GcsRemoteStorage`. The first ECOM-77 pass is
therefore a source/target contract and import-shape pass. Data loaders should be
added only after the target schemas exist in the fresh build.

## Module Disposition

| Data family | Legacy owner | ECOM-20 decision | ECOM-77 target path | Import disposition |
| --- | --- | --- | --- | --- |
| Sales rep | `Cminds_Salesrep`, `Vapewholesaleusa_CmindsSalesrep` | Keep / Defer | Install retained sales-rep module or replacement with compatible order/customer/admin-user/product fields. | Import after target schema exists. |
| Store credit | `Amasty_StoreCredit`, `Vapewholesaleusa_AmastyStoreCredit`, `Vapewholesaleusa_StoreCreditNotifier` | Keep / Defer | Install supported Amasty store-credit package or approved replacement. | Import balances and order/invoice/creditmemo totals only into approved schema. |
| Rewards | `Aheadworks_RewardPoints`, `Vapewholesaleusa_AheadworksRewardPoints`, `Vapewholesaleusa_RewardPointNotifier` | Keep / Defer | Install supported Aheadworks reward-points package or approved replacement. | Import balances/history and order/invoice/creditmemo reward fields only into approved schema. |
| Order source | `Vapewholesaleusa_OrderSource` | Keep / Defer | Retain or rewrite module with `vapewholesaleusa_ordersource_ordersource` equivalent. | Import selected rows after order and order-item ID maps exist. |
| Customer license expiration | `Vapewholesaleusa_CustomerLicense` | Keep / Defer | Retain customer EAV attributes. | Import customer datetime EAV values using customer ID and attribute ID maps. |
| Customer attachment/license files | `Vapewholesaleusa_CustomerAttachment` | Remove / Defer | Removed unless owner reverses disposition or replaces behavior. | Do not import table/files unless a new target path is approved. |
| Payment request | `Vapewholesaleusa_PaymentRequest` | Keep / Rewrite Candidate | Rewrite/retain behavior around invoice creation, email, hosted payment token, and webhook. | No standalone data table found; validate behavior/config rather than importing orphan rows. |

## Target Module Install Set

Install the owning modules before writing or running retained data loaders. The
minimum install set for ECOM-77 is summarized below. The operational install /
ignore checklist is maintained in
[module-install-ignore-matrix.md](module-install-ignore-matrix.md).

| Order | Module/package | Source | Why ECOM-77 needs it | Notes |
| ---: | --- | --- | --- | --- |
| 1 | `amasty/store-credit` | Composer, legacy constraint `^1.4` | Owns store-credit balances, history, and order total columns. | Requires Amasty repository credentials before `composer require`. |
| 2 | `aheadworks/module-reward-points` | Composer, legacy constraint `^2.5` | Owns reward balances, history, and order total columns. | Requires Aheadworks repository access. |
| 3 | `aheadworks/module-reward-points-graph-ql` | Composer, legacy constraint `^1.0` | Provides retained headless/API reward behavior if rewards survive frontend account/checkout flows. | Install with the reward-points package unless product explicitly drops reward GraphQL. |
| 4 | `Cminds_Salesrep` | Local module from `vusa244/app/code/Cminds/Salesrep` | Owns `salesrep`, `salesrep_commission_group`, admin-user columns, customer attr `salesrep_rep_id`, and product commission attrs. | Copy/rework into `app/code/Cminds/Salesrep`; review for PHP 8.4/Magento 2.4.8 compatibility before enabling. |
| 5 | `Vapewholesaleusa_CmindsSalesrep` | Local module | VUSA sales-rep admin grid/UI customizations. | Sequence depends on `Cminds_Salesrep`. |
| 6 | `Vapewholesaleusa_AmastyStoreCredit` | Local module | VUSA refund/store-credit behavior around Amasty Store Credit. | Sequence depends on `Amasty_StoreCredit`. |
| 7 | `Vapewholesaleusa_StoreCreditNotifier` | Local module | Notification behavior/config for store-credit balance changes. | Legacy composer metadata asks for `amasty/store-credit` `1.3.*`; update/verify for installed `^1.4`. |
| 8 | `Vapewholesaleusa_AheadworksRewardPoints` | Local module | VUSA reward-points UI/behavior customizations. | Sequence depends on `Aheadworks_RewardPoints`. |
| 9 | `Vapewholesaleusa_RewardPointNotifier` | Local module | Notification behavior/config for reward points. | Legacy composer metadata asks for `aheadworks/module-reward-points` `2.4.*`; update/verify for installed `^2.5`. |
| 10 | `Vapewholesaleusa_OrderSource` | Local module | Owns `vapewholesaleusa_ordersource_ordersource` and source-code fields on `integration`/`admin_user`. | Can be enabled after core sales/inventory modules. |
| 11 | `Vapewholesaleusa_CustomerLicense` | Local module | Owns customer EAV attrs `business_license_expiration` and `tobacco_license_expiration`. | Required before customer license EAV import. |
| 12 | `mageworx/module-ordereditor` | Composer, legacy constraint `^3.11` | Required by retained payment-request/order-editor behavior. | Install before `Vapewholesaleusa_MageWorxOrderEditor` and `Vapewholesaleusa_PaymentRequest`. |
| 13 | `Rootways_Authorizecim` | Local module from `vusa244/app/code/Rootways/Authorizecim` | Required by retained payment-request/payment behavior. | High-risk payment module; review compatibility before enabling. |
| 14 | `Vapewholesaleusa_RootwaysAuthorizecim` | Local module | VUSA Rootways payment override/logging behavior. | Depends on `Rootways_Authorizecim`. |
| 15 | `Vapewholesaleusa_MageWorxOrderEditor` | Local module | VUSA order-editor customization. | Depends on `MageWorx_OrderEditor`. |
| 16 | `Vapewholesaleusa_PaymentRequest` | Local module | Owns retained payment-request invoice/admin/email/webhook behavior. | Behavior/config validation only; no standalone data table has been identified for import. |

Not in the install set by current decision:

- `Vapewholesaleusa_CustomerAttachment`: ECOM-20 marks this module as Remove, so
  customer attachment rows/files are excluded unless the owner reverses that
  disposition.
- `Vapewholesaleusa_FluidPay`: ECOM-20 marks this module as Remove even though
  it is production-enabled. Treat it as a separate payment-module decision, not
  an ECOM-77 data-loader prerequisite.

## Source-To-Target Maps

### Sales Rep

| Source | Target | Notes |
| --- | --- | --- |
| `salesrep.salesrep_id` | target sales-rep primary key or generated key | Preserve as legacy reference if new IDs are generated. |
| `salesrep.order_id` | remapped `sales_order.entity_id` | Requires ECOM-7 order ID map. |
| `salesrep.rep_id`, `manager_id`, `coordinator_id` | remapped/validated `admin_user.user_id` | Drop or flag rows whose admin user is not retained. |
| `salesrep.rep_name`, `manager_name`, `coordinator_name` | same fields or audit snapshot | Keep names as historical order commission context. |
| `salesrep.*_commission_earned`, `*_commission_status` | same fields or replacement commission ledger | Currency precision should remain decimal. |
| `admin_user.salesrep_*` columns | target admin-user extension columns | Import only retained admin users. |
| customer attr `salesrep_rep_id` | target customer EAV/custom field | Requires customer and admin-user maps. |
| product attrs `salesrep_rep_commission_rate`, `salesrep_rep_commission_group` | target product EAV/custom fields | Requires product and commission-group maps. |
| `salesrep_commission_group` | target commission-group table | Import before product commission-group attributes. |

### Store Credit

| Source | Target | Notes |
| --- | --- | --- |
| `sales_order.amstorecredit_amount` and related order totals | approved order total columns | Existing ECOM-7 sample already selects store-credit orders from this field. |
| `sales_order_item.amstorecredit_amount` where present | approved item total columns | Import with order items if target schema supports item-level credit. |
| Amasty balance/history tables | approved Amasty/replacement tables | Source table names must be confirmed from the installed package schema or dump profile before loader work. |
| `store_credit_notifier_*` config | target config | Migrate only non-secret notification settings. |

### Rewards

| Source | Target | Notes |
| --- | --- | --- |
| `sales_order.aw_reward_points`, `aw_reward_points_amount`, `base_aw_reward_points_amount` | approved order reward columns | Existing ECOM-7 sample already selects reward orders from `aw_reward_points`. |
| `sales_order_item.aw_reward_points*` where present | approved item reward columns | Import with order items if target schema supports item-level rewards. |
| Aheadworks balance/history tables | approved Aheadworks/replacement tables | Source table names must be confirmed from package schema or dump profile before loader work. |
| `reward_point_notifier_*` config | target config | Migrate only non-secret notification settings. |

### Order Source

| Source | Target | Notes |
| --- | --- | --- |
| `vapewholesaleusa_ordersource_ordersource.entity_id` | target row key or generated key | Preserve as legacy reference if generated. |
| `order_id`, `order_inc_id` | remapped order ID and increment ID | Requires ECOM-7 order ID map. |
| `item_id`, `sku` | remapped order item ID and SKU | Requires order-item ID map; use SKU only as validation fallback. |
| `source_code` | MSI source code | Validate source exists in target inventory source config. |
| `qty`, `qty_shipped`, `status` | same fields | Keep shipment status semantics with the retained module. |
| `created_at`, `updated_at` | same fields | Preserve history timestamps. |
| `integration.source_code`, `admin_user.source_code` | target integration/admin-user source mapping | Import only retained integrations/admin users. |

### Customer License Expiration

| Source | Target | Notes |
| --- | --- | --- |
| customer EAV attr `business_license_expiration` | same customer datetime EAV attribute | Created by `Vapewholesaleusa_CustomerLicense`. |
| customer EAV attr `tobacco_license_expiration` | same customer datetime EAV attribute | Created by `Vapewholesaleusa_CustomerLicense`. |
| `customer_license/*` config | target config | Migrate non-secret cron/email settings after owner approval. |

### Customer Attachment/License Files

| Source | Target | Notes |
| --- | --- | --- |
| `customer_attachment.*` | none by current decision | ECOM-20 marks `Vapewholesaleusa_CustomerAttachment` as Remove, so table rows and files are excluded. |
| `pub/media/customer_attachment/*` | none by current decision | If reinstated, copy through the ECOM-78 media path and remap `customer_id`. |

### Payment Request

| Source | Target | Notes |
| --- | --- | --- |
| invoice form flag `payment_request_email` | behavior only | Request flag is transient, not persisted as a migration table. |
| core order/invoice/payment rows | already covered by ECOM-7 core import | Payment request sends hosted payment email for `mageworx_ordereditor_payment_method`. |
| `/V1/paymentrequest/statusupdate` webhook behavior | behavior validation | Validate API/headless behavior after rewrite/retain decision. |
| `vusa_payment_request/*` config | target config | Migrate only non-secret email/general settings. |

## Loader Sequence

1. Install or implement approved target modules/schemas.
2. Generate and review the source profile with [source-profile.sql](source-profile.sql).
3. Extend the ECOM-7 export set with only approved source tables/columns.
4. Load prerequisites in this order: admin users/source codes, customers,
   products, orders, order items, invoices, store-credit/reward balances,
   sales-rep commission groups, sales-rep rows, order-source rows, customer
   license EAV values.
5. Exclude `customer_attachment` until the module disposition changes.
6. Validate Admin grids/forms, order detail behavior, customer account data,
   reward/store-credit balances, payment-request emails/webhook, and any
   required headless APIs.

## Open Checks

- Confirm the exact installed Amasty and Aheadworks package versions and schema
  in the fresh target before writing store-credit/reward loaders.
- Decide whether sales-rep admin users are migrated as active users, disabled
  users, or historical lookup rows.
- Confirm whether `PaymentRequest` is retained as Magento behavior or replaced
  outside Magento.
- Decide whether the `CustomerAttachment` remove decision is final; current plan
  excludes its data and files.
