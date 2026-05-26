# ECOM-77 Module Install / Ignore Matrix

## Install For ECOM-77

These modules own schemas or APIs needed to import retained extension-backed
data. Install or port these before writing loaders.

| Decision | Module/package | Source | Required for | Install notes |
| --- | --- | --- | --- | --- |
| Install | `amasty/store-credit` | Composer | Store-credit balances, history, and order totals. | Use legacy constraint `^1.4`; requires Amasty repository access. |
| Install | `aheadworks/module-reward-points` | Composer | Reward balances, history, and order totals. | Use legacy constraint `^2.5`; requires Aheadworks repository access. |
| Install | `aheadworks/module-reward-points-graph-ql` | Composer | Headless reward account/checkout behavior. | Install with reward points unless the frontend explicitly drops reward APIs. |
| Port | `Cminds_Salesrep` | `vusa244/app/code/Cminds/Salesrep` | `salesrep`, `salesrep_commission_group`, sales-rep admin columns, customer attr, product attrs. | Review for Magento 2.4.8/PHP compatibility before enabling. |
| Port | `Vapewholesaleusa_CmindsSalesrep` | `vusa244/app/code/Vapewholesaleusa/CmindsSalesrep` | VUSA sales-rep grid/API/admin behavior. | Depends on `Cminds_Salesrep`. |
| Port | `Vapewholesaleusa_AmastyStoreCredit` | `vusa244/app/code/Vapewholesaleusa/AmastyStoreCredit` | Store-credit custom refund/quote behavior. | Depends on `Amasty_StoreCredit`. |
| Port | `Vapewholesaleusa_StoreCreditNotifier` | `vusa244/app/code/Vapewholesaleusa/StoreCreditNotifier` | Store-credit notification behavior/config. | Update legacy composer metadata from `amasty/store-credit` `1.3.*` to installed `^1.4` if composerized. |
| Port | `Vapewholesaleusa_AheadworksRewardPoints` | `vusa244/app/code/Vapewholesaleusa/AheadworksRewardPoints` | Reward-points custom UI/behavior. | Depends on `Aheadworks_RewardPoints`. |
| Port | `Vapewholesaleusa_RewardPointNotifier` | `vusa244/app/code/Vapewholesaleusa/RewardPointNotifier` | Reward notification behavior/config. | Update legacy composer metadata from `aheadworks/module-reward-points` `2.4.*` to installed `^2.5` if composerized. |
| Port | `Vapewholesaleusa_OrderSource` | `vusa244/app/code/Vapewholesaleusa/OrderSource` | `vapewholesaleusa_ordersource_ordersource`, `integration.source_code`, `admin_user.source_code`. | Enable after core sales/inventory modules exist. |
| Port | `Vapewholesaleusa_CustomerLicense` | `vusa244/app/code/Vapewholesaleusa/CustomerLicense` | Customer EAV attrs `business_license_expiration`, `tobacco_license_expiration`. | Required before importing customer license datetime values. |
| Install | `mageworx/module-ordereditor` | Composer | Required by retained `Vapewholesaleusa_PaymentRequest` legacy flow using `mageworx_ordereditor_payment_method`. | Use legacy constraint `^3.11`; requires MageWorx repository access. |
| Port | `Rootways_Authorizecim` | `vusa244/app/code/Rootways/Authorizecim` | Required by retained payment-request/payment behavior. | High-risk payment module; review compatibility before enabling. |
| Port | `Vapewholesaleusa_RootwaysAuthorizecim` | `vusa244/app/code/Vapewholesaleusa/RootwaysAuthorizecim` | VUSA Rootways payment override/logging behavior. | Depends on `Rootways_Authorizecim`; high-risk payment behavior. |
| Port | `Vapewholesaleusa_MageWorxOrderEditor` | `vusa244/app/code/Vapewholesaleusa/MageWorxOrderEditor` | VUSA order-editor customization retained with payment request flow. | Depends on `MageWorx_OrderEditor`. |
| Port | `Vapewholesaleusa_PaymentRequest` | `vusa244/app/code/Vapewholesaleusa/PaymentRequest` | Retained payment-request invoice/admin/email/webhook behavior. | Depends on `MageWorx_OrderEditor` and `Rootways_Authorizecim`; behavior/config validation, not standalone data import. |

## Retained But Not Data-Loader Owners

These modules are in scope because the payment/order-editor behavior is retained,
but they are not direct data-loader owners. They should be installed after the
core ECOM-77 data-owner modules are stable unless payment-request validation is
the next task.

| Decision | Module/package | Source | Retention note |
| --- | --- | --- | --- |
| Keep/install | `Vapewholesaleusa_PaymentRequest` | `vusa244/app/code/Vapewholesaleusa/PaymentRequest` | Behavior-only for invoice/admin/email/webhook; no standalone migration table found. Exact legacy module depends on `MageWorx_OrderEditor` and `Rootways_Authorizecim`. |
| Keep/install | `mageworx/module-ordereditor` | Composer | Required by retained `PaymentRequest` flow using `mageworx_ordereditor_payment_method`. |
| Keep/install | `Rootways_Authorizecim` | `vusa244/app/code/Rootways/Authorizecim` | Required by retained legacy Authorize.net CIM/payment-request dependency. |
| Keep/install | `Vapewholesaleusa_RootwaysAuthorizecim` | `vusa244/app/code/Vapewholesaleusa/RootwaysAuthorizecim` | Legacy Rootways override; high-risk payment behavior, not ECOM-77 data ownership. |
| Keep/install | `Vapewholesaleusa_MageWorxOrderEditor` | `vusa244/app/code/Vapewholesaleusa/MageWorxOrderEditor` | Order-editor customization retained with payment/order editing behavior. |

## Ignore For ECOM-77

These should not be installed for the ECOM-77 migration pass unless a separate
owner decision changes their disposition.

| Decision | Module/package | Source | Reason to ignore |
| --- | --- | --- | --- |
| Ignore | `Vapewholesaleusa_CustomerAttachment` | `vusa244/app/code/Vapewholesaleusa/CustomerAttachment` | ECOM-20 marks it Remove. Do not import `customer_attachment` rows or `pub/media/customer_attachment` files unless that decision changes. |
| Ignore | `Vapewholesaleusa_FluidPay` | `vusa244/app/code/Vapewholesaleusa/FluidPay` | ECOM-20 marks it Remove even though production config had it enabled. Treat as separate payment decision. |
| Ignore | `Mtn_AcceptBlue` | legacy production module | Production-enabled payment module, but no ECOM-77 extension data ownership found. |
| Ignore | `Amasty_Rewards`, `Amasty_RewardsGraphQl` | legacy production modules | Production-enabled, but ECOM-77 reward data path is Aheadworks-based via `aw_reward_points` fields and `Aheadworks_RewardPoints`. |
| Ignore | unrelated Amasty/MageWorx/Mirasvit/theme/search/blog modules | legacy Composer/local modules | Not part of retained extension-backed migration families in ECOM-77. |

## Practical Install Order

1. Add private Composer repositories/credentials outside Git.
2. Require `amasty/store-credit`, `aheadworks/module-reward-points`, and
   `aheadworks/module-reward-points-graph-ql`.
3. Port `Cminds_Salesrep`; fix compatibility issues; enable and run setup.
4. Port VUSA wrappers for sales rep, store credit, rewards, order source, and
   customer license.
5. Require `mageworx/module-ordereditor`, then port `Rootways_Authorizecim`,
   `Vapewholesaleusa_RootwaysAuthorizecim`,
   `Vapewholesaleusa_MageWorxOrderEditor`, and
   `Vapewholesaleusa_PaymentRequest`.
6. Run `setup:upgrade` and inspect the target schema.
7. Run `docs/ecom-77/source-profile.sql` against the legacy source slice.
8. Write/import loaders only for installed target schemas.

Payment-request validation is behavior/config validation; no standalone
payment-request data table has been identified for import.
