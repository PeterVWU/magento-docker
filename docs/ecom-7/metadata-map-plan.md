# ECOM-7 Metadata Map Plan

## Strategy

Use explicit source-to-target maps for the first rehearsal import.

The first pass should create target metadata by semantic key, not by copying
legacy IDs:

- customer groups by `customer_group_code`;
- websites by `code`;
- store groups by `code`;
- stores by `code`;
- product attribute sets by `attribute_set_name`;
- EAV attributes by `(entity_type_code, attribute_code)`;
- options by `(attribute_code, option label)`;
- categories by path/name mapping after the target category tree is seeded.

## Why

The clean target and legacy source do not share safe numeric IDs:

- source customer group `1` means `Retail Customer`;
- target customer group `1` means `General`;
- source product attribute sets include named sets that do not yet exist in the
  clean target;
- many sampled custom attributes are absent from the clean target.

Copying source IDs directly would make the rehearsal appear to load while
silently changing business meaning.

## First Seed Scope

Seed enough metadata to represent the first core-data run:

| Family | Source slice requirement |
| --- | ---: |
| Websites from sampled customers/orders | 2 |
| Stores from sampled customers/orders | 2 |
| Customer groups | 3 |
| Product attribute sets | 10 |
| Product attributes | 78 |
| Customer attributes | 16 |
| Referenced select-like scalar values | 559 |
| Directly linked categories | 40 |

## Deferred

- Do not seed retained extension module tables yet.
- Do not try to make custom attributes fully functional if the owning module is
  not installed. For the first rehearsal they are schema needed to preserve data,
  not proof that the related feature works.
- Do not seed media binaries; only metadata references are in scope until a
  source media copy exists.

## Next Implementation Slice

The first executable metadata slice should cover:

1. customer-group map;
2. website/store map;
3. product attribute-set map;
4. attribute map for the sampled product/customer attributes.

Options and categories come immediately after, because they depend on the target
attribute and category rows created by the first slice.

## Current Status

The metadata phase for the first rehearsal slice is now complete:

- customer groups mapped;
- websites, store groups, and stores mapped by semantic code, with the
  catalog-required third storefront added after the catalog EAV review;
- attribute sets mapped;
- referenced product/customer attributes seeded and mapped;
- 555 real option rows mapped;
- category ancestor closure seeded and mapped;
- required sampled product attribute-group assignments seeded.

The next executable slice is the core-data load transform.
