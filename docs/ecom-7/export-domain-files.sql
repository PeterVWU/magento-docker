-- Run after source-selection.sql and dataset-assembly.sql have created the
-- local rehearsal helper tables in the source schema.

SELECT o.*
FROM sales_order o
JOIN ecom7_order_sample sample ON sample.order_id = o.entity_id
ORDER BY o.entity_id;

SELECT item.*
FROM sales_order_item item
JOIN ecom7_order_sample sample ON sample.order_id = item.order_id
ORDER BY item.item_id;

SELECT address.*
FROM sales_order_address address
JOIN ecom7_address_sample sample ON sample.entity_id = address.entity_id
ORDER BY address.entity_id;

SELECT customer.*
FROM customer_entity customer
JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id
ORDER BY customer.entity_id;

SELECT address.*
FROM customer_address_entity address
JOIN ecom7_customer_address_sample sample ON sample.entity_id = address.entity_id
ORDER BY address.entity_id;

SELECT product.*
FROM catalog_product_entity product
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id
ORDER BY product.entity_id;

SELECT link.*
FROM catalog_product_super_link link
JOIN ecom7_order_item_product_sample sample ON sample.product_id = link.product_id
ORDER BY link.parent_id, link.product_id;

SELECT rep.*
FROM salesrep rep
JOIN ecom7_order_sample sample ON sample.order_id = rep.order_id
ORDER BY rep.salesrep_id;

SELECT source.*
FROM vapewholesaleusa_ordersource_ordersource source
JOIN ecom7_order_sample sample ON sample.order_id = source.order_id
ORDER BY source.entity_id;
