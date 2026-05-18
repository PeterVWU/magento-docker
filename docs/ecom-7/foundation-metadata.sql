-- Run after the selected slice and foundation source tables are loaded into
-- vusa_db0.

DROP TABLE IF EXISTS ecom7_product_attribute_sample;
CREATE TABLE ecom7_product_attribute_sample AS
SELECT DISTINCT attribute_id
FROM (
    SELECT attribute_id
    FROM catalog_product_entity_datetime value
    JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM catalog_product_entity_decimal value
    JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM catalog_product_entity_int value
    JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM catalog_product_entity_text value
    JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM catalog_product_entity_varchar value
    JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
) attributes_used;

DROP TABLE IF EXISTS ecom7_customer_attribute_sample;
CREATE TABLE ecom7_customer_attribute_sample AS
SELECT DISTINCT attribute_id
FROM (
    SELECT attribute_id
    FROM customer_entity_datetime value
    JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM customer_entity_decimal value
    JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM customer_entity_int value
    JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM customer_entity_text value
    JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
    UNION
    SELECT attribute_id
    FROM customer_entity_varchar value
    JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id
) attributes_used;

DROP TABLE IF EXISTS ecom7_category_sample;
CREATE TABLE ecom7_category_sample AS
SELECT DISTINCT category_id
FROM catalog_category_product link
JOIN ecom7_product_sample sample ON sample.product_id = link.product_id;

DROP TABLE IF EXISTS ecom7_attribute_option_sample;
CREATE TABLE ecom7_attribute_option_sample AS
SELECT DISTINCT value.value AS option_id
FROM catalog_product_entity_int value
JOIN ecom7_product_sample sample ON sample.product_id = value.entity_id
JOIN eav_attribute attribute ON attribute.attribute_id = value.attribute_id
WHERE attribute.frontend_input IN ('select', 'multiselect')
  AND value.value IS NOT NULL;

SELECT 'product_attributes' AS domain, COUNT(*) AS row_count
FROM ecom7_product_attribute_sample
UNION ALL
SELECT 'customer_attributes', COUNT(*)
FROM ecom7_customer_attribute_sample
UNION ALL
SELECT 'attribute_sets', COUNT(DISTINCT product.attribute_set_id)
FROM catalog_product_entity product
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id
UNION ALL
SELECT 'attribute_options', COUNT(*)
FROM ecom7_attribute_option_sample
UNION ALL
SELECT 'categories_directly_linked', COUNT(*)
FROM ecom7_category_sample
UNION ALL
SELECT 'websites_referenced', COUNT(DISTINCT customer.website_id)
FROM customer_entity customer
JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id
UNION ALL
SELECT 'stores_referenced', COUNT(DISTINCT orders.store_id)
FROM sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
UNION ALL
SELECT 'customer_groups_referenced', COUNT(DISTINCT customer.group_id)
FROM customer_entity customer
JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id;

SELECT
    attribute.entity_type_id,
    attribute.attribute_id,
    attribute.attribute_code,
    attribute.backend_type,
    attribute.frontend_input
FROM eav_attribute attribute
JOIN (
    SELECT attribute_id FROM ecom7_product_attribute_sample
    UNION
    SELECT attribute_id FROM ecom7_customer_attribute_sample
) sample ON sample.attribute_id = attribute.attribute_id
ORDER BY attribute.entity_type_id, attribute.attribute_id;

SELECT DISTINCT product.attribute_set_id, attr_set.attribute_set_name
FROM catalog_product_entity product
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id
JOIN eav_attribute_set attr_set ON attr_set.attribute_set_id = product.attribute_set_id
ORDER BY product.attribute_set_id;

SELECT DISTINCT customer.group_id, customer_group.customer_group_code
FROM customer_entity customer
JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id
JOIN customer_group ON customer_group.customer_group_id = customer.group_id
ORDER BY customer.group_id;
