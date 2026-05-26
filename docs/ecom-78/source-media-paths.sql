-- ECOM-78 source media path export.
--
-- Run after the ECOM-7 source-selection.sql and dataset-assembly.sql helper
-- tables exist in the source schema. The result sets identify media files that
-- the retained rehearsal dataset references. Export each result set as TSV
-- under an ignored local artifact directory.

-- Product gallery files for products retained by the rehearsal slice.
SELECT DISTINCT
    'product_gallery' AS media_family,
    'product' AS entity_kind,
    product.sku AS entity_key,
    CONCAT('catalog/product/', TRIM(LEADING '/' FROM gallery.value)) AS media_path,
    gallery.value AS raw_value
FROM catalog_product_entity_media_gallery gallery
JOIN catalog_product_entity_media_gallery_value_to_entity relation
    ON relation.value_id = gallery.value_id
JOIN catalog_product_entity product
    ON product.entity_id = relation.entity_id
JOIN ecom7_product_sample sample
    ON sample.product_id = product.entity_id
WHERE gallery.value IS NOT NULL
    AND gallery.value <> ''
ORDER BY entity_key, media_path;

-- Product image attributes that may reference files not linked through the
-- gallery table.
SELECT DISTINCT
    CONCAT('product_', attribute.attribute_code) AS media_family,
    'product' AS entity_kind,
    product.sku AS entity_key,
    CONCAT('catalog/product/', TRIM(LEADING '/' FROM value.value)) AS media_path,
    value.value AS raw_value
FROM catalog_product_entity_varchar value
JOIN eav_attribute attribute
    ON attribute.attribute_id = value.attribute_id
JOIN eav_entity_type entity_type
    ON entity_type.entity_type_id = attribute.entity_type_id
    AND entity_type.entity_type_code = 'catalog_product'
JOIN catalog_product_entity product
    ON product.entity_id = value.entity_id
JOIN ecom7_product_sample sample
    ON sample.product_id = product.entity_id
WHERE attribute.attribute_code IN ('image', 'small_image', 'thumbnail', 'swatch_image')
    AND value.value IS NOT NULL
    AND value.value NOT IN ('', 'no_selection')
ORDER BY entity_key, media_family, media_path;

-- Category media for categories attached to retained products.
SELECT DISTINCT
    CONCAT('category_', attribute.attribute_code) AS media_family,
    'category' AS entity_kind,
    CAST(category.entity_id AS CHAR) AS entity_key,
    CASE
        WHEN value.value LIKE 'catalog/category/%' THEN value.value
        ELSE CONCAT('catalog/category/', TRIM(LEADING '/' FROM value.value))
    END AS media_path,
    value.value AS raw_value
FROM catalog_category_entity_varchar value
JOIN eav_attribute attribute
    ON attribute.attribute_id = value.attribute_id
JOIN eav_entity_type entity_type
    ON entity_type.entity_type_id = attribute.entity_type_id
    AND entity_type.entity_type_code = 'catalog_category'
JOIN catalog_category_entity category
    ON category.entity_id = value.entity_id
JOIN catalog_category_product category_product
    ON category_product.category_id = category.entity_id
JOIN ecom7_product_sample sample
    ON sample.product_id = category_product.product_id
WHERE attribute.attribute_code IN ('image', 'thumbnail')
    AND value.value IS NOT NULL
    AND value.value NOT IN ('', 'no_selection')
ORDER BY entity_key, media_family, media_path;

-- Customer attachment files tied to retained customers. These are the likely
-- customer-license document binaries for the legacy CustomerAttachment module.
-- If this table is not present in a given source extract, record that as an
-- ECOM-77/ECOM-78 dependency rather than skipping license media silently.
SELECT DISTINCT
    'customer_attachment' AS media_family,
    'customer' AS entity_kind,
    CAST(attachment.customer_id AS CHAR) AS entity_key,
    TRIM(LEADING '/' FROM attachment.file) AS media_path,
    attachment.file AS raw_value
FROM customer_attachment attachment
JOIN ecom7_customer_sample sample
    ON sample.customer_id = attachment.customer_id
WHERE attachment.file IS NOT NULL
    AND attachment.file <> ''
ORDER BY entity_key, media_path;
