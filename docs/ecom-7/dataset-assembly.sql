-- Run in the same MySQL session immediately after source-selection.sql.
-- It summarizes the dependent rows required by the chosen 50-order sample.

DROP TABLE IF EXISTS ecom7_customer_sample;
CREATE TABLE ecom7_customer_sample AS
SELECT DISTINCT o.customer_id
FROM sales_order o
JOIN ecom7_order_sample sample ON sample.order_id = o.entity_id
WHERE o.customer_id IS NOT NULL;

DROP TABLE IF EXISTS ecom7_order_item_product_sample;
CREATE TABLE ecom7_order_item_product_sample AS
SELECT DISTINCT item.product_id
FROM sales_order_item item
JOIN ecom7_order_sample sample ON sample.order_id = item.order_id
WHERE item.product_id IS NOT NULL;

DROP TABLE IF EXISTS ecom7_product_sample;
CREATE TABLE ecom7_product_sample AS
SELECT product_id
FROM ecom7_order_item_product_sample
UNION
SELECT link.parent_id AS product_id
FROM catalog_product_super_link link
JOIN ecom7_order_item_product_sample sample
    ON sample.product_id = link.product_id;

DROP TABLE IF EXISTS ecom7_address_sample;
CREATE TABLE ecom7_address_sample AS
SELECT DISTINCT address.entity_id
FROM sales_order_address address
JOIN ecom7_order_sample sample ON sample.order_id = address.parent_id;

DROP TABLE IF EXISTS ecom7_customer_address_sample;
CREATE TABLE ecom7_customer_address_sample AS
SELECT DISTINCT address.entity_id
FROM customer_address_entity address
JOIN ecom7_customer_sample sample ON sample.customer_id = address.parent_id;

SELECT 'orders' AS domain, COUNT(*) AS row_count
FROM ecom7_order_sample
UNION ALL
SELECT 'customers', COUNT(*)
FROM ecom7_customer_sample
UNION ALL
SELECT 'order_addresses', COUNT(*)
FROM ecom7_address_sample
UNION ALL
SELECT 'customer_addresses', COUNT(*)
FROM ecom7_customer_address_sample
UNION ALL
SELECT 'order_items', COUNT(*)
FROM sales_order_item item
JOIN ecom7_order_sample sample ON sample.order_id = item.order_id
UNION ALL
SELECT 'order_item_products', COUNT(*)
FROM ecom7_order_item_product_sample
UNION ALL
SELECT 'products_with_configurable_parents', COUNT(*)
FROM ecom7_product_sample
UNION ALL
SELECT 'configurable_parent_links', COUNT(*)
FROM catalog_product_super_link link
JOIN ecom7_order_item_product_sample sample ON sample.product_id = link.product_id
UNION ALL
SELECT 'salesrep_rows', COUNT(*)
FROM salesrep rep
JOIN ecom7_order_sample sample ON sample.order_id = rep.order_id
UNION ALL
SELECT 'ordersource_rows', COUNT(*)
FROM vapewholesaleusa_ordersource_ordersource source
JOIN ecom7_order_sample sample ON sample.order_id = source.order_id;

SELECT
    item.product_type,
    COUNT(*) AS item_count
FROM sales_order_item item
JOIN ecom7_order_sample sample ON sample.order_id = item.order_id
GROUP BY item.product_type
ORDER BY item_count DESC, item.product_type;

SELECT
    COUNT(DISTINCT sample.customer_id) AS customers,
    COUNT(DISTINCT entity.entity_id) AS customer_rows_found,
    COUNT(DISTINCT address.entity_id) AS customer_address_rows_found
FROM ecom7_customer_sample sample
LEFT JOIN customer_entity entity ON entity.entity_id = sample.customer_id
LEFT JOIN customer_address_entity address ON address.parent_id = sample.customer_id;
