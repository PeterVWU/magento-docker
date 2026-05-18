-- Seed product attribute groups and sampled set assignments needed by the slice.

INSERT INTO magento.eav_attribute_group (
    attribute_set_id,
    attribute_group_name,
    sort_order,
    default_id,
    attribute_group_code,
    tab_group_code
)
SELECT DISTINCT
    set_map.target_attribute_set_id,
    source_group.attribute_group_name,
    source_group.sort_order,
    source_group.default_id,
    source_group.attribute_group_code,
    source_group.tab_group_code
FROM ecom7_attribute_set_map set_map
JOIN eav_attribute_group source_group
    ON source_group.attribute_set_id = set_map.source_attribute_set_id
JOIN eav_entity_attribute source_assignment
    ON source_assignment.attribute_group_id = source_group.attribute_group_id
JOIN ecom7_product_attribute_sample sampled_attribute
    ON sampled_attribute.attribute_id = source_assignment.attribute_id
LEFT JOIN magento.eav_attribute_group target_group
    ON target_group.attribute_set_id = set_map.target_attribute_set_id
   AND target_group.attribute_group_code = source_group.attribute_group_code
WHERE target_group.attribute_group_id IS NULL;

DROP TABLE IF EXISTS ecom7_seeded_attribute_group_map;
CREATE TABLE ecom7_seeded_attribute_group_map AS
SELECT
    source_group.attribute_group_id AS source_attribute_group_id,
    target_group.attribute_group_id AS target_attribute_group_id,
    source_group.attribute_set_id AS source_attribute_set_id,
    set_map.target_attribute_set_id,
    source_group.attribute_group_name
FROM eav_attribute_group source_group
JOIN ecom7_attribute_set_map set_map
    ON set_map.source_attribute_set_id = source_group.attribute_set_id
JOIN magento.eav_attribute_group target_group
    ON target_group.attribute_set_id = set_map.target_attribute_set_id
   AND target_group.attribute_group_code = source_group.attribute_group_code;

INSERT INTO magento.eav_entity_attribute (
    entity_type_id,
    attribute_set_id,
    attribute_group_id,
    attribute_id,
    sort_order
)
SELECT
    target_entity_type.entity_type_id,
    set_map.target_attribute_set_id,
    group_map.target_attribute_group_id,
    attribute_map.seeded_target_attribute_id,
    source_assignment.sort_order
FROM eav_entity_attribute source_assignment
JOIN ecom7_product_attribute_sample sampled_attribute
    ON sampled_attribute.attribute_id = source_assignment.attribute_id
JOIN ecom7_attribute_set_map set_map
    ON set_map.source_attribute_set_id = source_assignment.attribute_set_id
JOIN ecom7_seeded_attribute_group_map group_map
    ON group_map.source_attribute_group_id = source_assignment.attribute_group_id
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = source_assignment.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
JOIN magento.eav_entity_type target_entity_type
    ON target_entity_type.entity_type_code = 'catalog_product'
LEFT JOIN magento.eav_entity_attribute target_assignment
    ON target_assignment.attribute_set_id = set_map.target_attribute_set_id
   AND target_assignment.attribute_id = attribute_map.seeded_target_attribute_id
WHERE target_assignment.entity_attribute_id IS NULL;

SELECT
    source_set.attribute_set_name,
    COUNT(DISTINCT source_assignment.attribute_id) AS sampled_source_assignments,
    COUNT(DISTINCT target_assignment.attribute_id) AS target_assignments
FROM ecom7_attribute_set_map set_map
JOIN eav_attribute_set source_set
    ON source_set.attribute_set_id = set_map.source_attribute_set_id
JOIN eav_entity_attribute source_assignment
    ON source_assignment.attribute_set_id = source_set.attribute_set_id
JOIN ecom7_product_attribute_sample sampled_attribute
    ON sampled_attribute.attribute_id = source_assignment.attribute_id
LEFT JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = source_assignment.attribute_id
   AND attribute_map.entity_type_code = 'catalog_product'
LEFT JOIN magento.eav_entity_attribute target_assignment
    ON target_assignment.attribute_set_id = set_map.target_attribute_set_id
   AND target_assignment.attribute_id = attribute_map.seeded_target_attribute_id
GROUP BY source_set.attribute_set_name
ORDER BY source_set.attribute_set_name;
