-- Load retained extension-backed data for the ECOM-7 first rehearsal slice.
--
-- Run this in the legacy source schema (`vusa_db0`) after:
-- - ECOM-7 core first-slice data is loaded into `magento`;
-- - retained ECOM-77 modules have run setup:upgrade in `magento`;
-- - a target DB snapshot has been taken.
--
-- This loader only moves data that exists in the selective source schema. It
-- does not fabricate Amasty/Aheadworks balance-history tables when those source
-- tables were not extracted.

SET SESSION group_concat_max_len = 65535;

DROP PROCEDURE IF EXISTS ecom77_update_common_extension_columns;
DELIMITER //
CREATE PROCEDURE ecom77_update_common_extension_columns(
    IN stage_table_name VARCHAR(128),
    IN target_table_name VARCHAR(128),
    IN key_column_name VARCHAR(128)
)
BEGIN
    DECLARE update_assignments TEXT;

    SELECT GROUP_CONCAT(
               CONCAT(
                   'target.`',
                   source_columns.column_name,
                   '` = stage.`',
                   source_columns.column_name,
                   '`'
               )
               ORDER BY source_columns.ordinal_position SEPARATOR ', '
           )
    INTO update_assignments
    FROM information_schema.columns source_columns
    JOIN information_schema.columns target_columns
        ON target_columns.table_schema = 'magento'
       AND target_columns.table_name = target_table_name
       AND target_columns.column_name = source_columns.column_name
    WHERE source_columns.table_schema = DATABASE()
      AND source_columns.table_name = stage_table_name
      AND source_columns.column_name <> key_column_name
      AND (
          source_columns.column_name LIKE 'amstorecredit%'
          OR source_columns.column_name LIKE 'aw_reward%'
          OR source_columns.column_name LIKE 'base_aw_reward%'
      );

    IF update_assignments IS NOT NULL THEN
        SET @update_sql = CONCAT(
            'UPDATE magento.`',
            target_table_name,
            '` target JOIN `',
            stage_table_name,
            '` stage ON stage.`',
            key_column_name,
            '` = target.`',
            key_column_name,
            '` SET ',
            update_assignments
        );

        PREPARE update_statement FROM @update_sql;
        EXECUTE update_statement;
        DEALLOCATE PREPARE update_statement;
    END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS ecom77_insert_common_columns;
DELIMITER //
CREATE PROCEDURE ecom77_insert_common_columns(
    IN stage_table_name VARCHAR(128),
    IN target_table_name VARCHAR(128),
    IN key_column_name VARCHAR(128)
)
BEGIN
    DECLARE common_columns TEXT;
    DECLARE insert_columns TEXT;
    DECLARE select_columns TEXT;

    SELECT
        GROUP_CONCAT(CONCAT('`', source_columns.column_name, '`')
                     ORDER BY source_columns.ordinal_position SEPARATOR ', '),
        GROUP_CONCAT(CONCAT('stage.`', source_columns.column_name, '`')
                     ORDER BY source_columns.ordinal_position SEPARATOR ', ')
    INTO common_columns, select_columns
    FROM information_schema.columns source_columns
    JOIN information_schema.columns target_columns
        ON target_columns.table_schema = 'magento'
       AND target_columns.table_name = target_table_name
       AND target_columns.column_name = source_columns.column_name
    WHERE source_columns.table_schema = DATABASE()
      AND source_columns.table_name = stage_table_name;

    SET insert_columns = common_columns;

    IF insert_columns IS NOT NULL THEN
        SET @insert_sql = CONCAT(
            'INSERT INTO magento.`',
            target_table_name,
            '` (',
            insert_columns,
            ') SELECT ',
            select_columns,
            ' FROM `',
            stage_table_name,
            '` stage LEFT JOIN magento.`',
            target_table_name,
            '` target ON target.`',
            key_column_name,
            '` = stage.`',
            key_column_name,
            '` WHERE target.`',
            key_column_name,
            '` IS NULL'
        );

        PREPARE insert_statement FROM @insert_sql;
        EXECUTE insert_statement;
        DEALLOCATE PREPARE insert_statement;
    END IF;
END//
DELIMITER ;

-- Store-credit and reward order totals.
DROP TABLE IF EXISTS ecom77_stage_sales_order;
CREATE TABLE ecom77_stage_sales_order LIKE sales_order;
INSERT INTO ecom77_stage_sales_order
SELECT orders.*
FROM sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
JOIN magento.sales_order target_order ON target_order.entity_id = orders.entity_id;
CALL ecom77_update_common_extension_columns(
    'ecom77_stage_sales_order',
    'sales_order',
    'entity_id'
);

DROP TABLE IF EXISTS ecom77_stage_sales_order_item;
CREATE TABLE ecom77_stage_sales_order_item LIKE sales_order_item;
INSERT INTO ecom77_stage_sales_order_item
SELECT item.*
FROM sales_order_item item
JOIN ecom7_order_sample sample ON sample.order_id = item.order_id
JOIN magento.sales_order_item target_item ON target_item.item_id = item.item_id;
CALL ecom77_update_common_extension_columns(
    'ecom77_stage_sales_order_item',
    'sales_order_item',
    'item_id'
);

DROP TABLE IF EXISTS ecom77_stage_sales_invoice;
CREATE TABLE ecom77_stage_sales_invoice LIKE sales_invoice;
INSERT INTO ecom77_stage_sales_invoice
SELECT invoice.*
FROM sales_invoice invoice
JOIN ecom7_order_sample sample ON sample.order_id = invoice.order_id
JOIN magento.sales_invoice target_invoice
    ON target_invoice.entity_id = invoice.entity_id;
CALL ecom77_update_common_extension_columns(
    'ecom77_stage_sales_invoice',
    'sales_invoice',
    'entity_id'
);

DROP TABLE IF EXISTS ecom77_stage_sales_invoice_item;
CREATE TABLE ecom77_stage_sales_invoice_item LIKE sales_invoice_item;
INSERT INTO ecom77_stage_sales_invoice_item
SELECT item.*
FROM sales_invoice_item item
JOIN sales_invoice invoice ON invoice.entity_id = item.parent_id
JOIN ecom7_order_sample sample ON sample.order_id = invoice.order_id
JOIN magento.sales_invoice_item target_item
    ON target_item.entity_id = item.entity_id;
CALL ecom77_update_common_extension_columns(
    'ecom77_stage_sales_invoice_item',
    'sales_invoice_item',
    'entity_id'
);

-- Sales-rep commission rows. Historical rep names are preserved even when the
-- selective source schema does not include admin_user rows.
DROP TABLE IF EXISTS ecom77_stage_salesrep;
CREATE TABLE ecom77_stage_salesrep LIKE salesrep;
INSERT INTO ecom77_stage_salesrep
SELECT rep.*
FROM salesrep rep
JOIN ecom7_order_sample sample ON sample.order_id = rep.order_id
JOIN magento.sales_order target_order ON target_order.entity_id = rep.order_id;
CALL ecom77_insert_common_columns(
    'ecom77_stage_salesrep',
    'salesrep',
    'salesrep_id'
);

-- OrderSource rows. The current first slice profiles as zero rows, but this
-- stays active so later slices can reuse the loader without code changes.
DROP TABLE IF EXISTS ecom77_stage_ordersource;
CREATE TABLE ecom77_stage_ordersource LIKE vapewholesaleusa_ordersource_ordersource;
INSERT INTO ecom77_stage_ordersource
SELECT source.*
FROM vapewholesaleusa_ordersource_ordersource source
JOIN ecom7_order_sample sample ON sample.order_id = source.order_id
JOIN magento.sales_order target_order ON target_order.entity_id = source.order_id
LEFT JOIN magento.sales_order_item target_item
    ON target_item.item_id = source.item_id
WHERE source.item_id IS NULL
   OR target_item.item_id IS NOT NULL;
CALL ecom77_insert_common_columns(
    'ecom77_stage_ordersource',
    'vapewholesaleusa_ordersource_ordersource',
    'entity_id'
);

-- Customer-license expiration attributes.
DROP TABLE IF EXISTS ecom77_stage_customer_license_datetime;
CREATE TABLE ecom77_stage_customer_license_datetime (
    source_value_id INT NOT NULL PRIMARY KEY,
    source_attribute_code VARCHAR(255) NOT NULL,
    target_attribute_id SMALLINT UNSIGNED NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    value DATETIME NULL
);

INSERT INTO ecom77_stage_customer_license_datetime (
    source_value_id,
    source_attribute_code,
    target_attribute_id,
    entity_id,
    value
)
SELECT
    source_value.value_id,
    source_attr.attribute_code,
    target_attr.attribute_id,
    source_value.entity_id,
    source_value.value
FROM customer_entity_datetime source_value
JOIN eav_attribute source_attr
    ON source_attr.attribute_id = source_value.attribute_id
JOIN eav_entity_type source_type
    ON source_type.entity_type_id = source_attr.entity_type_id
JOIN magento.eav_entity_type target_type
    ON target_type.entity_type_code = source_type.entity_type_code
JOIN magento.eav_attribute target_attr
    ON target_attr.entity_type_id = target_type.entity_type_id
   AND target_attr.attribute_code = source_attr.attribute_code
JOIN ecom7_customer_sample sample
    ON sample.customer_id = source_value.entity_id
JOIN magento.customer_entity target_customer
    ON target_customer.entity_id = source_value.entity_id
WHERE source_type.entity_type_code = 'customer'
  AND source_attr.attribute_code IN (
      'business_license_expiration',
      'tobacco_license_expiration'
  )
  AND source_value.value IS NOT NULL;

UPDATE magento.customer_entity_datetime target_value
JOIN ecom77_stage_customer_license_datetime stage
    ON stage.entity_id = target_value.entity_id
   AND stage.target_attribute_id = target_value.attribute_id
SET target_value.value = stage.value;

INSERT INTO magento.customer_entity_datetime (
    attribute_id,
    entity_id,
    value
)
SELECT
    stage.target_attribute_id,
    stage.entity_id,
    stage.value
FROM ecom77_stage_customer_license_datetime stage
LEFT JOIN magento.customer_entity_datetime target_value
    ON target_value.entity_id = stage.entity_id
   AND target_value.attribute_id = stage.target_attribute_id
WHERE target_value.value_id IS NULL;

DROP PROCEDURE ecom77_update_common_extension_columns;
DROP PROCEDURE ecom77_insert_common_columns;

SELECT 'salesrep_rows_loaded' AS metric, COUNT(*) AS row_count
FROM magento.salesrep rep
JOIN ecom7_order_sample sample ON sample.order_id = rep.order_id
UNION ALL
SELECT 'ordersource_rows_loaded', COUNT(*)
FROM magento.vapewholesaleusa_ordersource_ordersource source
JOIN ecom7_order_sample sample ON sample.order_id = source.order_id
UNION ALL
SELECT 'store_credit_orders_loaded', COUNT(*)
FROM magento.sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
WHERE orders.amstorecredit_amount IS NOT NULL
  AND orders.amstorecredit_amount <> 0
UNION ALL
SELECT 'reward_orders_loaded', COUNT(*)
FROM magento.sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
WHERE orders.aw_reward_points IS NOT NULL
  AND orders.aw_reward_points <> 0
UNION ALL
SELECT 'customer_license_values_loaded', COUNT(*)
FROM magento.customer_entity_datetime value
JOIN magento.eav_attribute attr ON attr.attribute_id = value.attribute_id
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
WHERE attr.attribute_code IN (
    'business_license_expiration',
    'tobacco_license_expiration'
)
  AND value.value IS NOT NULL;
