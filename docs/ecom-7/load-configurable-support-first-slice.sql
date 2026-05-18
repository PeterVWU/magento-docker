-- Backfill configurable-product support rows omitted from the first catalog load.

DROP TABLE IF EXISTS ecom7_stage_catalog_product_relation;
CREATE TABLE ecom7_stage_catalog_product_relation LIKE catalog_product_relation;
INSERT INTO ecom7_stage_catalog_product_relation
SELECT relation.*
FROM catalog_product_relation relation
JOIN ecom7_product_sample sample ON sample.product_id = relation.child_id;

INSERT INTO magento.catalog_product_relation
SELECT staged.*
FROM ecom7_stage_catalog_product_relation staged
LEFT JOIN magento.catalog_product_relation target
    ON target.parent_id = staged.parent_id
   AND target.child_id = staged.child_id
WHERE target.child_id IS NULL;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_super_attribute;
CREATE TABLE ecom7_stage_catalog_product_super_attribute LIKE catalog_product_super_attribute;
INSERT INTO ecom7_stage_catalog_product_super_attribute
SELECT super_attribute.*
FROM catalog_product_super_attribute super_attribute
WHERE super_attribute.product_id IN (
    SELECT DISTINCT link.parent_id
    FROM catalog_product_super_link link
    JOIN ecom7_product_sample sample ON sample.product_id = link.product_id
);

UPDATE ecom7_stage_catalog_product_super_attribute staged
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
SET staged.attribute_id = attribute_map.seeded_target_attribute_id;

INSERT INTO magento.catalog_product_super_attribute
SELECT staged.*
FROM ecom7_stage_catalog_product_super_attribute staged
LEFT JOIN magento.catalog_product_super_attribute target
    ON target.product_super_attribute_id = staged.product_super_attribute_id
WHERE target.product_super_attribute_id IS NULL;

DROP TABLE IF EXISTS ecom7_stage_catalog_product_super_attribute_label;
CREATE TABLE ecom7_stage_catalog_product_super_attribute_label LIKE catalog_product_super_attribute_label;
INSERT INTO ecom7_stage_catalog_product_super_attribute_label
SELECT label.*
FROM catalog_product_super_attribute_label label
JOIN ecom7_stage_catalog_product_super_attribute super_attribute
    ON super_attribute.product_super_attribute_id = label.product_super_attribute_id;

UPDATE ecom7_stage_catalog_product_super_attribute_label staged
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = staged.store_id
SET staged.store_id = store_map.target_store_id;

INSERT INTO magento.catalog_product_super_attribute_label
SELECT staged.*
FROM ecom7_stage_catalog_product_super_attribute_label staged
LEFT JOIN magento.catalog_product_super_attribute_label target
    ON target.value_id = staged.value_id
WHERE target.value_id IS NULL;

SELECT 'catalog_product_relation' AS domain, COUNT(*) AS row_count
FROM magento.catalog_product_relation
WHERE child_id IN (SELECT product_id FROM ecom7_product_sample)
UNION ALL
SELECT 'catalog_product_super_attribute', COUNT(*)
FROM magento.catalog_product_super_attribute
WHERE product_id IN (
    SELECT DISTINCT parent_id
    FROM magento.catalog_product_super_link
    WHERE product_id IN (SELECT product_id FROM ecom7_product_sample)
)
UNION ALL
SELECT 'catalog_product_super_attribute_label', COUNT(*)
FROM magento.catalog_product_super_attribute_label label
JOIN magento.catalog_product_super_attribute super_attribute
    ON super_attribute.product_super_attribute_id = label.product_super_attribute_id
WHERE super_attribute.product_id IN (
    SELECT DISTINCT parent_id
    FROM magento.catalog_product_super_link
    WHERE product_id IN (SELECT product_id FROM ecom7_product_sample)
);
