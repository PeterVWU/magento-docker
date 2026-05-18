-- Load the catalog portion of the first rehearsal slice into the clean target.
-- Run after the foundation metadata seed scripts.

SET SESSION group_concat_max_len = 65535;

DROP PROCEDURE IF EXISTS ecom7_insert_common_columns;
DELIMITER //
CREATE PROCEDURE ecom7_insert_common_columns(
    IN stage_table_name VARCHAR(128),
    IN target_table_name VARCHAR(128)
)
BEGIN
    DECLARE common_columns TEXT;

    SELECT GROUP_CONCAT(CONCAT('`', source_columns.column_name, '`')
                        ORDER BY source_columns.ordinal_position SEPARATOR ', ')
    INTO common_columns
    FROM information_schema.columns source_columns
    JOIN information_schema.columns target_columns
        ON target_columns.table_schema = 'magento'
       AND target_columns.table_name = target_table_name
       AND target_columns.column_name = source_columns.column_name
    WHERE source_columns.table_schema = DATABASE()
      AND source_columns.table_name = stage_table_name;

    SET @insert_sql = CONCAT(
        'INSERT INTO magento.`',
        target_table_name,
        '` (',
        common_columns,
        ') SELECT ',
        common_columns,
        ' FROM `',
        stage_table_name,
        '`'
    );

    PREPARE insert_statement FROM @insert_sql;
    EXECUTE insert_statement;
    DEALLOCATE PREPARE insert_statement;
END//
DELIMITER ;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity;
CREATE TABLE ecom7_stage_catalog_product_entity LIKE catalog_product_entity;
INSERT INTO ecom7_stage_catalog_product_entity
SELECT product.*
FROM catalog_product_entity product
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id
LEFT JOIN magento.catalog_product_entity target_product
    ON target_product.entity_id = product.entity_id
WHERE target_product.entity_id IS NULL;

UPDATE ecom7_stage_catalog_product_entity staged_product
JOIN ecom7_attribute_set_map set_map
    ON set_map.source_attribute_set_id = staged_product.attribute_set_id
SET staged_product.attribute_set_id = set_map.target_attribute_set_id;

CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity',
    'catalog_product_entity'
);

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_datetime;
CREATE TABLE ecom7_stage_catalog_product_entity_datetime LIKE catalog_product_entity_datetime;
INSERT INTO ecom7_stage_catalog_product_entity_datetime
SELECT value.*
FROM catalog_product_entity_datetime value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_decimal;
CREATE TABLE ecom7_stage_catalog_product_entity_decimal LIKE catalog_product_entity_decimal;
INSERT INTO ecom7_stage_catalog_product_entity_decimal
SELECT value.*
FROM catalog_product_entity_decimal value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_int;
CREATE TABLE ecom7_stage_catalog_product_entity_int LIKE catalog_product_entity_int;
INSERT INTO ecom7_stage_catalog_product_entity_int
SELECT value.*
FROM catalog_product_entity_int value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_text;
CREATE TABLE ecom7_stage_catalog_product_entity_text LIKE catalog_product_entity_text;
INSERT INTO ecom7_stage_catalog_product_entity_text
SELECT value.*
FROM catalog_product_entity_text value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_varchar;
CREATE TABLE ecom7_stage_catalog_product_entity_varchar LIKE catalog_product_entity_varchar;
INSERT INTO ecom7_stage_catalog_product_entity_varchar
SELECT value.*
FROM catalog_product_entity_varchar value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id;

UPDATE ecom7_stage_catalog_product_entity_datetime staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_value.store_id
SET
    staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000,
    staged_value.store_id = store_map.target_store_id;
UPDATE ecom7_stage_catalog_product_entity_datetime
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_catalog_product_entity_decimal staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_value.store_id
SET
    staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000,
    staged_value.store_id = store_map.target_store_id;
UPDATE ecom7_stage_catalog_product_entity_decimal
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_catalog_product_entity_int staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_value.store_id
LEFT JOIN ecom7_seeded_option_map option_map
    ON option_map.source_option_id = staged_value.value
SET
    staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000,
    staged_value.store_id = store_map.target_store_id,
    staged_value.value = COALESCE(option_map.target_option_id, staged_value.value);
UPDATE ecom7_stage_catalog_product_entity_int
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_catalog_product_entity_text staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_value.store_id
SET
    staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000,
    staged_value.store_id = store_map.target_store_id;
UPDATE ecom7_stage_catalog_product_entity_text
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_catalog_product_entity_varchar staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_value.store_id
SET
    staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000,
    staged_value.store_id = store_map.target_store_id;
UPDATE ecom7_stage_catalog_product_entity_varchar
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_datetime',
    'catalog_product_entity_datetime'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_decimal',
    'catalog_product_entity_decimal'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_int',
    'catalog_product_entity_int'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_text',
    'catalog_product_entity_text'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_varchar',
    'catalog_product_entity_varchar'
);

DROP TABLE IF EXISTS ecom7_stage_catalog_product_super_link;
CREATE TABLE ecom7_stage_catalog_product_super_link LIKE catalog_product_super_link;
INSERT INTO ecom7_stage_catalog_product_super_link
SELECT link.*
FROM catalog_product_super_link link
JOIN ecom7_product_sample sample ON sample.product_id = link.product_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_super_link',
    'catalog_product_super_link'
);

-- Product website assignments are required for storefront/GraphQL visibility.
-- Load them with docs/ecom-7/load-product-websites-first-slice.sql.

-- Configurable products also require:
-- - catalog_product_relation
-- - catalog_product_super_attribute
-- - catalog_product_super_attribute_label
-- Load them with docs/ecom-7/load-configurable-support-first-slice.sql.

DROP TABLE IF EXISTS ecom7_stage_catalog_category_product;
CREATE TABLE ecom7_stage_catalog_category_product LIKE catalog_category_product;
INSERT INTO ecom7_stage_catalog_category_product
SELECT link.*
FROM catalog_category_product link
JOIN ecom7_product_sample sample ON sample.product_id = link.product_id;
UPDATE ecom7_stage_catalog_category_product staged_link
JOIN ecom7_seeded_category_map category_map
    ON category_map.source_category_id = staged_link.category_id
SET staged_link.category_id = category_map.target_category_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_category_product',
    'catalog_category_product'
);

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_media_gallery_value_to_entity;
CREATE TABLE ecom7_stage_catalog_product_entity_media_gallery_value_to_entity
LIKE catalog_product_entity_media_gallery_value_to_entity;
INSERT INTO ecom7_stage_catalog_product_entity_media_gallery_value_to_entity
SELECT relation.*
FROM catalog_product_entity_media_gallery_value_to_entity relation
JOIN ecom7_product_sample sample ON sample.product_id = relation.entity_id;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_media_gallery;
CREATE TABLE ecom7_stage_catalog_product_entity_media_gallery
LIKE catalog_product_entity_media_gallery;
INSERT INTO ecom7_stage_catalog_product_entity_media_gallery
SELECT gallery.*
FROM catalog_product_entity_media_gallery gallery
JOIN ecom7_stage_catalog_product_entity_media_gallery_value_to_entity relation
    ON relation.value_id = gallery.value_id;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_entity_media_gallery_value;
CREATE TABLE ecom7_stage_catalog_product_entity_media_gallery_value
LIKE catalog_product_entity_media_gallery_value;
INSERT INTO ecom7_stage_catalog_product_entity_media_gallery_value
SELECT gallery_value.*
FROM catalog_product_entity_media_gallery_value gallery_value
JOIN ecom7_stage_catalog_product_entity_media_gallery_value_to_entity relation
    ON relation.value_id = gallery_value.value_id;

UPDATE ecom7_stage_catalog_product_entity_media_gallery staged_gallery
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_gallery.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
SET staged_gallery.attribute_id = attribute_map.seeded_target_attribute_id + 10000;
UPDATE ecom7_stage_catalog_product_entity_media_gallery
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_catalog_product_entity_media_gallery_value staged_value
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_value.store_id
SET staged_value.store_id = store_map.target_store_id;

CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_media_gallery',
    'catalog_product_entity_media_gallery'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_media_gallery_value',
    'catalog_product_entity_media_gallery_value'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_catalog_product_entity_media_gallery_value_to_entity',
    'catalog_product_entity_media_gallery_value_to_entity'
);

DROP TABLE IF EXISTS ecom7_stage_cataloginventory_stock_item;
CREATE TABLE ecom7_stage_cataloginventory_stock_item LIKE cataloginventory_stock_item;
INSERT INTO ecom7_stage_cataloginventory_stock_item
SELECT stock.*
FROM cataloginventory_stock_item stock
JOIN ecom7_product_sample sample ON sample.product_id = stock.product_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_cataloginventory_stock_item',
    'cataloginventory_stock_item'
);

DROP TABLE IF EXISTS ecom7_stage_inventory_source_item;
CREATE TABLE ecom7_stage_inventory_source_item LIKE inventory_source_item;
INSERT INTO ecom7_stage_inventory_source_item
SELECT source_item.*
FROM inventory_source_item source_item
JOIN catalog_product_entity product ON product.sku = source_item.sku
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_inventory_source_item',
    'inventory_source_item'
);

DROP PROCEDURE ecom7_insert_common_columns;

SELECT 'catalog_product_entity' AS domain, COUNT(*) AS row_count
FROM magento.catalog_product_entity
WHERE entity_id IN (SELECT product_id FROM ecom7_product_sample)
UNION ALL
SELECT 'catalog_product_entity_int', COUNT(*)
FROM magento.catalog_product_entity_int
WHERE entity_id IN (SELECT product_id FROM ecom7_product_sample)
UNION ALL
SELECT 'catalog_category_product', COUNT(*)
FROM magento.catalog_category_product
WHERE product_id IN (SELECT product_id FROM ecom7_product_sample)
UNION ALL
SELECT 'catalog_product_super_link', COUNT(*)
FROM magento.catalog_product_super_link
WHERE product_id IN (SELECT product_id FROM ecom7_product_sample)
UNION ALL
SELECT 'cataloginventory_stock_item', COUNT(*)
FROM magento.cataloginventory_stock_item
WHERE product_id IN (SELECT product_id FROM ecom7_product_sample)
UNION ALL
SELECT 'inventory_source_item', COUNT(*)
FROM magento.inventory_source_item source_item
JOIN catalog_product_entity product
    ON product.sku = source_item.sku
JOIN ecom7_product_sample sample
    ON sample.product_id = product.entity_id;
