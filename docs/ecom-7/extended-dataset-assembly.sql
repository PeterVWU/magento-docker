-- Run after source-selection.sql, dataset-assembly.sql, and the next required
-- source tables are loaded into vusa_db0.

SELECT 'catalog_product_entity_datetime' AS domain, COUNT(*) AS row_count
FROM catalog_product_entity_datetime value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
UNION ALL
SELECT 'catalog_product_entity_decimal', COUNT(*)
FROM catalog_product_entity_decimal value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
UNION ALL
SELECT 'catalog_product_entity_int', COUNT(*)
FROM catalog_product_entity_int value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
UNION ALL
SELECT 'catalog_product_entity_text', COUNT(*)
FROM catalog_product_entity_text value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
UNION ALL
SELECT 'catalog_product_entity_varchar', COUNT(*)
FROM catalog_product_entity_varchar value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
UNION ALL
SELECT 'customer_entity_datetime', COUNT(*)
FROM customer_entity_datetime value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
UNION ALL
SELECT 'customer_entity_decimal', COUNT(*)
FROM customer_entity_decimal value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
UNION ALL
SELECT 'customer_entity_int', COUNT(*)
FROM customer_entity_int value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
UNION ALL
SELECT 'customer_entity_text', COUNT(*)
FROM customer_entity_text value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
UNION ALL
SELECT 'customer_entity_varchar', COUNT(*)
FROM customer_entity_varchar value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
UNION ALL
SELECT 'customer_address_entity_datetime', COUNT(*)
FROM customer_address_entity_datetime value
JOIN ecom7_customer_address_sample sample ON sample.entity_id = value.entity_id
UNION ALL
SELECT 'customer_address_entity_decimal', COUNT(*)
FROM customer_address_entity_decimal value
JOIN ecom7_customer_address_sample sample ON sample.entity_id = value.entity_id
UNION ALL
SELECT 'customer_address_entity_int', COUNT(*)
FROM customer_address_entity_int value
JOIN ecom7_customer_address_sample sample ON sample.entity_id = value.entity_id
UNION ALL
SELECT 'customer_address_entity_text', COUNT(*)
FROM customer_address_entity_text value
JOIN ecom7_customer_address_sample sample ON sample.entity_id = value.entity_id
UNION ALL
SELECT 'customer_address_entity_varchar', COUNT(*)
FROM customer_address_entity_varchar value
JOIN ecom7_customer_address_sample sample ON sample.entity_id = value.entity_id
UNION ALL
SELECT 'catalog_category_product', COUNT(*)
FROM catalog_category_product value
JOIN ecom7_product_sample sample ON sample.product_id = value.product_id
UNION ALL
SELECT 'catalog_product_entity_media_gallery_value_to_entity', COUNT(*)
FROM catalog_product_entity_media_gallery_value_to_entity value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
UNION ALL
SELECT 'sales_order_payment', COUNT(*)
FROM sales_order_payment payment
JOIN ecom7_order_sample sample ON sample.order_id = payment.parent_id
UNION ALL
SELECT 'sales_order_status_history', COUNT(*)
FROM sales_order_status_history history
JOIN ecom7_order_sample sample ON sample.order_id = history.parent_id
UNION ALL
SELECT 'sales_invoice', COUNT(*)
FROM sales_invoice invoice
JOIN ecom7_order_sample sample ON sample.order_id = invoice.order_id
UNION ALL
SELECT 'sales_invoice_item', COUNT(*)
FROM sales_invoice_item item
JOIN sales_invoice invoice ON invoice.entity_id = item.parent_id
JOIN ecom7_order_sample sample ON sample.order_id = invoice.order_id
UNION ALL
SELECT 'sales_shipment', COUNT(*)
FROM sales_shipment shipment
JOIN ecom7_order_sample sample ON sample.order_id = shipment.order_id
UNION ALL
SELECT 'sales_shipment_item', COUNT(*)
FROM sales_shipment_item item
JOIN sales_shipment shipment ON shipment.entity_id = item.parent_id
JOIN ecom7_order_sample sample ON sample.order_id = shipment.order_id
UNION ALL
SELECT 'cataloginventory_stock_item', COUNT(*)
FROM cataloginventory_stock_item stock
JOIN ecom7_product_sample sample ON sample.product_id = stock.product_id
UNION ALL
SELECT 'inventory_source_item', COUNT(*)
FROM inventory_source_item source
JOIN catalog_product_entity product ON product.sku = source.sku
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id;
