-- Build source-to-target comparison maps. Run after foundation-metadata.sql.

DROP TABLE IF EXISTS ecom7_customer_group_map;
CREATE TABLE ecom7_customer_group_map AS
SELECT
    source.customer_group_id AS source_customer_group_id,
    source.customer_group_code,
    target.customer_group_id AS target_customer_group_id
FROM customer_group source
JOIN (
    SELECT DISTINCT customer.group_id
    FROM customer_entity customer
    JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id
) used ON used.group_id = source.customer_group_id
LEFT JOIN magento.customer_group target
    ON target.customer_group_code = source.customer_group_code;

DROP TABLE IF EXISTS ecom7_attribute_set_map;
CREATE TABLE ecom7_attribute_set_map AS
SELECT DISTINCT
    source.attribute_set_id AS source_attribute_set_id,
    source.attribute_set_name,
    target.attribute_set_id AS target_attribute_set_id
FROM catalog_product_entity product
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id
JOIN eav_attribute_set source ON source.attribute_set_id = product.attribute_set_id
LEFT JOIN magento.eav_attribute_set target
    ON target.entity_type_id = 4
   AND target.attribute_set_name = source.attribute_set_name;

DROP TABLE IF EXISTS ecom7_attribute_map;
CREATE TABLE ecom7_attribute_map AS
SELECT
    entity_type.entity_type_code,
    source.attribute_id AS source_attribute_id,
    source.attribute_code,
    source.backend_type,
    source.frontend_input,
    target.attribute_id AS target_attribute_id
FROM eav_attribute source
JOIN eav_entity_type entity_type
    ON entity_type.entity_type_id = source.entity_type_id
JOIN (
    SELECT attribute_id FROM ecom7_product_attribute_sample
    UNION
    SELECT attribute_id FROM ecom7_customer_attribute_sample
) used ON used.attribute_id = source.attribute_id
LEFT JOIN magento.eav_entity_type target_entity_type
    ON target_entity_type.entity_type_code = entity_type.entity_type_code
LEFT JOIN magento.eav_attribute target
    ON target.entity_type_id = target_entity_type.entity_type_id
   AND target.attribute_code = source.attribute_code;

SELECT *
FROM ecom7_customer_group_map
ORDER BY source_customer_group_id;

SELECT *
FROM ecom7_attribute_set_map
ORDER BY source_attribute_set_id;

SELECT
    entity_type_code,
    COUNT(*) AS referenced_attributes,
    SUM(target_attribute_id IS NOT NULL) AS already_present,
    SUM(target_attribute_id IS NULL) AS missing_in_target
FROM ecom7_attribute_map
GROUP BY entity_type_code
ORDER BY entity_type_code;
