-- Compare source and target table shapes before the first core-data load.

DROP TABLE IF EXISTS ecom7_core_load_tables;
CREATE TABLE ecom7_core_load_tables (
    table_name VARCHAR(128) NOT NULL PRIMARY KEY
);

INSERT INTO ecom7_core_load_tables (table_name) VALUES
    ('catalog_product_entity'),
    ('catalog_product_entity_datetime'),
    ('catalog_product_entity_decimal'),
    ('catalog_product_entity_int'),
    ('catalog_product_entity_text'),
    ('catalog_product_entity_varchar'),
    ('catalog_product_super_link'),
    ('catalog_category_product'),
    ('catalog_product_entity_media_gallery'),
    ('catalog_product_entity_media_gallery_value'),
    ('catalog_product_entity_media_gallery_value_to_entity'),
    ('cataloginventory_stock_item'),
    ('inventory_source_item'),
    ('customer_entity'),
    ('customer_entity_datetime'),
    ('customer_entity_decimal'),
    ('customer_entity_int'),
    ('customer_entity_text'),
    ('customer_entity_varchar'),
    ('customer_address_entity'),
    ('customer_address_entity_datetime'),
    ('customer_address_entity_decimal'),
    ('customer_address_entity_int'),
    ('customer_address_entity_text'),
    ('customer_address_entity_varchar'),
    ('sales_order'),
    ('sales_order_item'),
    ('sales_order_address'),
    ('sales_order_payment'),
    ('sales_order_status_history'),
    ('sales_invoice'),
    ('sales_invoice_item'),
    ('sales_shipment'),
    ('sales_shipment_item');

SELECT
    load_table.table_name,
    source_columns.column_count AS source_column_count,
    target_columns.column_count AS target_column_count
FROM ecom7_core_load_tables load_table
LEFT JOIN (
    SELECT table_name, COUNT(*) AS column_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    GROUP BY table_name
) source_columns ON source_columns.table_name = load_table.table_name
LEFT JOIN (
    SELECT table_name, COUNT(*) AS column_count
    FROM information_schema.columns
    WHERE table_schema = 'magento'
    GROUP BY table_name
) target_columns ON target_columns.table_name = load_table.table_name
ORDER BY load_table.table_name;

SELECT
    'source_only' AS difference_type,
    source_columns.table_name,
    source_columns.column_name
FROM information_schema.columns source_columns
JOIN ecom7_core_load_tables load_table
    ON load_table.table_name = source_columns.table_name
LEFT JOIN information_schema.columns target_columns
    ON target_columns.table_schema = 'magento'
   AND target_columns.table_name = source_columns.table_name
   AND target_columns.column_name = source_columns.column_name
WHERE source_columns.table_schema = DATABASE()
  AND target_columns.column_name IS NULL
UNION ALL
SELECT
    'target_only' AS difference_type,
    target_columns.table_name,
    target_columns.column_name
FROM information_schema.columns target_columns
JOIN ecom7_core_load_tables load_table
    ON load_table.table_name = target_columns.table_name
LEFT JOIN information_schema.columns source_columns
    ON source_columns.table_schema = DATABASE()
   AND source_columns.table_name = target_columns.table_name
   AND source_columns.column_name = target_columns.column_name
WHERE target_columns.table_schema = 'magento'
  AND source_columns.column_name IS NULL
ORDER BY table_name, difference_type, column_name;
