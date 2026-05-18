-- Load the sales graph for the first rehearsal slice.

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

DROP TABLE IF EXISTS ecom7_stage_sales_order;
CREATE TABLE ecom7_stage_sales_order LIKE sales_order;
INSERT INTO ecom7_stage_sales_order
SELECT orders.*
FROM sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
LEFT JOIN magento.sales_order target_order
    ON target_order.entity_id = orders.entity_id
WHERE target_order.entity_id IS NULL;

UPDATE ecom7_stage_sales_order staged_order
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_order.store_id
LEFT JOIN ecom7_customer_group_map group_map
    ON group_map.source_customer_group_id = staged_order.customer_group_id
SET
    staged_order.store_id = store_map.target_store_id,
    staged_order.customer_group_id = COALESCE(
        group_map.target_customer_group_id,
        staged_order.customer_group_id
    );

CALL ecom7_insert_common_columns('ecom7_stage_sales_order', 'sales_order');

DROP TABLE IF EXISTS ecom7_stage_sales_order_item;
CREATE TABLE ecom7_stage_sales_order_item LIKE sales_order_item;
INSERT INTO ecom7_stage_sales_order_item
SELECT item.*
FROM sales_order_item item
JOIN ecom7_order_sample sample ON sample.order_id = item.order_id;

UPDATE ecom7_stage_sales_order_item staged_item
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_item.store_id
SET staged_item.store_id = store_map.target_store_id;

CALL ecom7_insert_common_columns(
    'ecom7_stage_sales_order_item',
    'sales_order_item'
);

DROP TABLE IF EXISTS ecom7_stage_sales_order_address;
CREATE TABLE ecom7_stage_sales_order_address LIKE sales_order_address;
INSERT INTO ecom7_stage_sales_order_address
SELECT address.*
FROM sales_order_address address
JOIN ecom7_address_sample sample ON sample.entity_id = address.entity_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_sales_order_address',
    'sales_order_address'
);

DROP TABLE IF EXISTS ecom7_stage_sales_order_payment;
CREATE TABLE ecom7_stage_sales_order_payment LIKE sales_order_payment;
INSERT INTO ecom7_stage_sales_order_payment
SELECT payment.*
FROM sales_order_payment payment
JOIN ecom7_order_sample sample ON sample.order_id = payment.parent_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_sales_order_payment',
    'sales_order_payment'
);

DROP TABLE IF EXISTS ecom7_stage_sales_order_status_history;
CREATE TABLE ecom7_stage_sales_order_status_history LIKE sales_order_status_history;
INSERT INTO ecom7_stage_sales_order_status_history
SELECT history.*
FROM sales_order_status_history history
JOIN ecom7_order_sample sample ON sample.order_id = history.parent_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_sales_order_status_history',
    'sales_order_status_history'
);

DROP TABLE IF EXISTS ecom7_stage_sales_invoice;
CREATE TABLE ecom7_stage_sales_invoice LIKE sales_invoice;
INSERT INTO ecom7_stage_sales_invoice
SELECT invoice.*
FROM sales_invoice invoice
JOIN ecom7_order_sample sample ON sample.order_id = invoice.order_id;

UPDATE ecom7_stage_sales_invoice staged_invoice
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_invoice.store_id
SET staged_invoice.store_id = store_map.target_store_id;

CALL ecom7_insert_common_columns('ecom7_stage_sales_invoice', 'sales_invoice');

DROP TABLE IF EXISTS ecom7_stage_sales_invoice_item;
CREATE TABLE ecom7_stage_sales_invoice_item LIKE sales_invoice_item;
INSERT INTO ecom7_stage_sales_invoice_item
SELECT item.*
FROM sales_invoice_item item
JOIN sales_invoice invoice ON invoice.entity_id = item.parent_id
JOIN ecom7_order_sample sample ON sample.order_id = invoice.order_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_sales_invoice_item',
    'sales_invoice_item'
);

DROP TABLE IF EXISTS ecom7_stage_sales_shipment;
CREATE TABLE ecom7_stage_sales_shipment LIKE sales_shipment;
INSERT INTO ecom7_stage_sales_shipment
SELECT shipment.*
FROM sales_shipment shipment
JOIN ecom7_order_sample sample ON sample.order_id = shipment.order_id;

UPDATE ecom7_stage_sales_shipment staged_shipment
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged_shipment.store_id
SET staged_shipment.store_id = store_map.target_store_id;

CALL ecom7_insert_common_columns('ecom7_stage_sales_shipment', 'sales_shipment');

DROP TABLE IF EXISTS ecom7_stage_sales_shipment_item;
CREATE TABLE ecom7_stage_sales_shipment_item LIKE sales_shipment_item;
INSERT INTO ecom7_stage_sales_shipment_item
SELECT item.*
FROM sales_shipment_item item
JOIN sales_shipment shipment ON shipment.entity_id = item.parent_id
JOIN ecom7_order_sample sample ON sample.order_id = shipment.order_id;
CALL ecom7_insert_common_columns(
    'ecom7_stage_sales_shipment_item',
    'sales_shipment_item'
);

DROP PROCEDURE ecom7_insert_common_columns;

SELECT 'sales_order' AS domain, COUNT(*) AS row_count
FROM magento.sales_order
WHERE entity_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_order_item', COUNT(*)
FROM magento.sales_order_item
WHERE order_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_order_address', COUNT(*)
FROM magento.sales_order_address
WHERE parent_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_order_payment', COUNT(*)
FROM magento.sales_order_payment
WHERE parent_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_order_status_history', COUNT(*)
FROM magento.sales_order_status_history
WHERE parent_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_invoice', COUNT(*)
FROM magento.sales_invoice
WHERE order_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_invoice_item', COUNT(*)
FROM magento.sales_invoice_item item
JOIN magento.sales_invoice invoice ON invoice.entity_id = item.parent_id
WHERE invoice.order_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_shipment', COUNT(*)
FROM magento.sales_shipment
WHERE order_id IN (SELECT order_id FROM ecom7_order_sample)
UNION ALL
SELECT 'sales_shipment_item', COUNT(*)
FROM magento.sales_shipment_item item
JOIN magento.sales_shipment shipment ON shipment.entity_id = item.parent_id
WHERE shipment.order_id IN (SELECT order_id FROM ecom7_order_sample);

-- Direct SQL inserts do not execute Magento's model save hooks. After loading
-- the sales graph, refresh the derived admin grids with:
-- docker compose exec -T php php scripts/ecom7/refresh-missing-sales-grids.php
