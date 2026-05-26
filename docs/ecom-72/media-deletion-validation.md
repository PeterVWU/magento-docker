# ECOM-72 Media Deletion Validation

Status: Validated in staging on 2026-05-26

## Current Result

Owner validation confirmed media delete and replace behavior works through the
`Vapewholesaleusa_GcsRemoteStorage` `gcs-s3` adapter in staging.

Result summary:

- Magento DB/media references update as expected after delete or replace
  operations;
- Admin and frontend no longer render deleted media after expected cache
  refresh;
- replacement media renders through the staging GCS-backed media path.

## Goal

Validate delete and replace behavior for media managed through
`Vapewholesaleusa_GcsRemoteStorage` in staging.

The intended behavior is that Magento admin operations update Magento database
references and either remove the backing GCS object or make it unreachable from
Magento/CDN. Generated cache and rendition objects may require targeted cleanup;
that behavior must be documented rather than assumed.

## Preconditions

- ECOM-71 or equivalent disposable product media exists in staging.
- A disposable category image and CMS/Media Gallery asset are available.
- Staging remote storage uses `gcs-s3` with the ECOM-78 bucket layout.
- Object listings and screenshots are stored in ignored artifacts.

## Artifact Layout

```text
var/import/ecom72-media-delete/
  product-before.tsv
  product-after.tsv
  category-before.tsv
  category-after.tsv
  cms-before.tsv
  cms-after.tsv
  validation-notes.md
```

## Product Image Delete

1. Create or select a disposable product with at least two gallery images.
2. Capture media DB rows and GCS object paths before deletion.
3. In Admin, remove one gallery image and save the product.
4. Clean targeted caches only:

```bash
bin/magento cache:clean block_html full_page
```

5. Confirm the deleted image no longer appears in Admin product edit.
6. Confirm product frontend/API no longer references the deleted image.
7. Compare original and generated GCS object paths before and after deletion.

SQL probes:

```sql
SELECT gallery.value, value.disabled, value.label
FROM catalog_product_entity e
JOIN catalog_product_entity_media_gallery_value_to_entity link
  ON link.entity_id = e.entity_id
JOIN catalog_product_entity_media_gallery gallery
  ON gallery.value_id = link.value_id
LEFT JOIN catalog_product_entity_media_gallery_value value
  ON value.value_id = gallery.value_id
WHERE e.sku = 'ECOM72-PRODUCT'
ORDER BY gallery.value;
```

## Product Image Replace

1. Replace the base image with a new file that has a distinct filename.
2. Save the product and clean targeted caches.
3. Confirm `image`, `small_image`, and `thumbnail` attributes reference the new
   path where expected.
4. Confirm the old URL no longer appears in GraphQL or product page markup.
5. Confirm the new URL returns HTTP 200.

Attribute probe:

```sql
SELECT attribute.attribute_code, value.value
FROM catalog_product_entity e
JOIN catalog_product_entity_varchar value
  ON value.entity_id = e.entity_id
JOIN eav_attribute attribute
  ON attribute.attribute_id = value.attribute_id
WHERE e.sku = 'ECOM72-PRODUCT'
  AND attribute.attribute_code IN ('image', 'small_image', 'thumbnail');
```

## Category And CMS Media Delete

Validate one category media field and one Media Gallery/CMS asset:

1. Capture the current DB value and GCS object path.
2. Remove or replace the asset through Admin.
3. Save, then clean targeted caches.
4. Confirm Admin no longer shows the deleted asset.
5. Confirm frontend/CMS content no longer references the deleted asset.
6. Confirm GCS object state and generated cache/rendition behavior.

## Expected GCS Outcomes

Record which path family each delete affects:

| Path Family | Expected Result | Notes |
| --- | --- | --- |
| Original object | Removed or unreachable after Magento delete. | If retained, document why and how cleanup runs. |
| `cache/` product image | Removed by targeted cleanup or naturally regenerated without references. | Must not remain referenced by Magento output. |
| `.renditions/` Media Gallery image | Removed, regenerated, or left orphaned with documented cleanup. | Must not remain visible in Admin for deleted asset. |
| CDN object | Purged or made stale-safe by versioned path. | Covered in more detail by ECOM-74. |

## Pass Criteria

- Magento DB references are updated for product, category, and CMS/Media Gallery
  delete or replace operations.
- Frontend and Admin no longer render deleted media after targeted cache clean.
- New replacement media renders from GCS.
- Any orphaned original/cache/rendition object behavior is documented with a
  cleanup decision.
- No workflow requires direct manual edits to shared local `pub/media`.

## Failure Rules

Open a follow-up blocker if:

- deleted media remains referenced in product/category/CMS output;
- Admin shows broken thumbnails after expected targeted cache clean;
- the adapter reports successful delete while the object remains reachable when
  it should be removed;
- replacement media writes locally but not to GCS;
- delete behavior differs between product, category, and Media Gallery assets
  without an operational explanation.
