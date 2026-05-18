# ECOM-7 Foundation Metadata Findings

## Summary

The first 50-order rehearsal slice cannot be imported safely by preserving
legacy IDs directly.

## Source Slice Requirements

| Metadata family | Referenced by first slice |
| --- | ---: |
| Product attributes | 78 |
| Customer attributes | 16 |
| Product attribute sets | 10 |
| Referenced select-like scalar values | 559 |
| Directly linked categories | 40 |
| Websites from sampled customers/orders | 2 |
| Stores from sampled customers/orders | 2 |
| Customer groups | 3 |

## Clean Target Comparison

The clean `magento-modern` target currently has Magento core plus the GCS
validation module only.

The source slice references custom or third-party fields that do not exist in
the clean target, including examples such as:

- customer: `tax_ein`, `tobacco_license`, `business_name`,
  `business_license_expiration`, `salesrep_rep_id`, `hub_contact_id`;
- product: `brand`, `flavor`, `nicotine_level`, `style`,
  `salesrep_rep_commission_group`, `hub_product_id`.

The target also does not contain the legacy named product attribute sets used by
the slice:

- `Pod Systems`
- `Starter Kits`
- `Disposables`
- `Coils`
- `Replacement Pods`
- `E-liquid`
- `Accessories`
- `Alternatives`
- `Nic Pouches`

Customer-group IDs are not semantically compatible:

| Legacy ID | Legacy name | Clean target ID with same numeric value | Clean target name |
| ---: | --- | ---: | --- |
| 1 | `Retail Customer` | 1 | `General` |
| 2 | `Wholesale` | 2 | `Wholesale` |
| 8 | `Hawaii Tax Paid` | n/a | n/a |

## Decision

Use an explicit metadata seed/remap layer for the first import rehearsal.

Do not preserve legacy IDs blindly for:

- customer groups;
- attribute sets;
- custom attributes;
- attribute options;
- categories;
- store/website scope.

Core import preparation now needs to:

1. seed or map the required metadata in the clean target;
2. generate source-to-target ID maps;
3. transform exported entity/EAV rows through those maps before loading them.

This remains compatible with the core-first plan: the metadata required for
retained extension fields may be seeded as inert schema so source rows can be
represented, while extension business logic remains deferred until those modules
are installed.

## Seed Status

The first rehearsal seed pass now covers:

| Family | Result |
| --- | --- |
| Customer groups | `Retail Customer`, `Wholesale`, and `Hawaii Tax Paid` all map to target groups. |
| Product attribute sets | All 10 referenced source sets now map to target sets. |
| Product attributes | All 77 referenced sampled attributes now exist in target. |
| Customer attributes | All 16 referenced sampled attributes now exist in target. |

For missing custom attributes whose source model belonged to an unavailable
third-party module, the rehearsal seed stores the attribute schema but clears
the unavailable handler so the core-first target does not instantiate missing
classes before those modules are installed.

Additional seed work now completed:

| Family | Result |
| --- | --- |
| Select options | 555 real `eav_attribute_option` rows mapped; the other four sampled scalar values are direct enum-like values such as `status`, `visibility`, and `tax_class_id`, not option rows. |
| Store scope | `base/default`, `nichero_website/nichero`, and catalog-required `vapeguysinc_website/vapeguysinc` now exist in target by semantic code. |
| Category tree | 42 closure categories mapped, including required ancestors for the 40 directly linked categories. |
| Product attribute layout | Required attribute groups and sampled set assignments seeded for the referenced product sets. |

Foundation metadata is now sufficient to move into the first core-data load
transform pass.
