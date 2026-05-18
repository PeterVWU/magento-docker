-- Rehearsal-only metadata seed for the clean target schema.
--
-- This deliberately seeds by semantic key rather than reusing legacy IDs.

-- Tax classes required by customer groups in the first slice.
INSERT INTO magento.tax_class (class_name, class_type)
SELECT DISTINCT source_tax.class_name, source_tax.class_type
FROM customer_group source_group
JOIN (
    SELECT DISTINCT customer.group_id
    FROM customer_entity customer
    JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id
) used_group ON used_group.group_id = source_group.customer_group_id
JOIN tax_class source_tax ON source_tax.class_id = source_group.tax_class_id
LEFT JOIN magento.tax_class target_tax
    ON target_tax.class_name = source_tax.class_name
   AND target_tax.class_type = source_tax.class_type
WHERE target_tax.class_id IS NULL;

-- Customer groups required by the first slice.
INSERT INTO magento.customer_group (customer_group_code, tax_class_id)
SELECT
    source_group.customer_group_code,
    target_tax.class_id
FROM customer_group source_group
JOIN (
    SELECT DISTINCT customer.group_id
    FROM customer_entity customer
    JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id
) used_group ON used_group.group_id = source_group.customer_group_id
JOIN tax_class source_tax ON source_tax.class_id = source_group.tax_class_id
JOIN magento.tax_class target_tax
    ON target_tax.class_name = source_tax.class_name
   AND target_tax.class_type = source_tax.class_type
LEFT JOIN magento.customer_group target_group
    ON target_group.customer_group_code = source_group.customer_group_code
WHERE target_group.customer_group_id IS NULL;

-- Product attribute sets required by the first slice.
INSERT INTO magento.eav_attribute_set (entity_type_id, attribute_set_name, sort_order)
SELECT DISTINCT
    target_entity_type.entity_type_id,
    source_set.attribute_set_name,
    source_set.sort_order
FROM catalog_product_entity product
JOIN ecom7_product_sample sample ON sample.product_id = product.entity_id
JOIN eav_attribute_set source_set ON source_set.attribute_set_id = product.attribute_set_id
JOIN magento.eav_entity_type target_entity_type
    ON target_entity_type.entity_type_code = 'catalog_product'
LEFT JOIN magento.eav_attribute_set target_set
    ON target_set.entity_type_id = target_entity_type.entity_type_id
   AND target_set.attribute_set_name = source_set.attribute_set_name
WHERE target_set.attribute_set_id IS NULL;

-- Missing product/customer attributes referenced by the slice. Third-party
-- source models are nulled so absent modules do not break the core-first run.
INSERT INTO magento.eav_attribute (
    entity_type_id,
    attribute_code,
    attribute_model,
    backend_model,
    backend_type,
    backend_table,
    frontend_model,
    frontend_input,
    frontend_label,
    frontend_class,
    source_model,
    is_required,
    is_user_defined,
    default_value,
    is_unique,
    note
)
SELECT
    target_entity_type.entity_type_id,
    source.attribute_code,
    CASE
        WHEN source.attribute_model LIKE 'Magento\\\\%' THEN source.attribute_model
        ELSE NULL
    END,
    CASE
        WHEN source.backend_model LIKE 'Magento\\\\%' THEN source.backend_model
        ELSE NULL
    END,
    source.backend_type,
    source.backend_table,
    CASE
        WHEN source.frontend_model LIKE 'Magento\\\\%' THEN source.frontend_model
        ELSE NULL
    END,
    source.frontend_input,
    source.frontend_label,
    source.frontend_class,
    CASE
        WHEN source.source_model LIKE 'Magento\\\\%' THEN source.source_model
        ELSE NULL
    END,
    source.is_required,
    source.is_user_defined,
    source.default_value,
    source.is_unique,
    source.note
FROM eav_attribute source
JOIN eav_entity_type source_entity_type
    ON source_entity_type.entity_type_id = source.entity_type_id
JOIN magento.eav_entity_type target_entity_type
    ON target_entity_type.entity_type_code = source_entity_type.entity_type_code
JOIN (
    SELECT attribute_id FROM ecom7_product_attribute_sample
    UNION
    SELECT attribute_id FROM ecom7_customer_attribute_sample
) used ON used.attribute_id = source.attribute_id
LEFT JOIN magento.eav_attribute target
    ON target.entity_type_id = target_entity_type.entity_type_id
   AND target.attribute_code = source.attribute_code
WHERE target.attribute_id IS NULL;

-- Rebuild the mapping table now that missing attributes have target IDs.
DROP TABLE IF EXISTS ecom7_seeded_attribute_map;
CREATE TABLE ecom7_seeded_attribute_map AS
SELECT
    map.*,
    target.attribute_id AS seeded_target_attribute_id
FROM ecom7_attribute_map map
JOIN magento.eav_entity_type target_entity_type
    ON target_entity_type.entity_type_code = map.entity_type_code
LEFT JOIN magento.eav_attribute target
    ON target.entity_type_id = target_entity_type.entity_type_id
   AND target.attribute_code = map.attribute_code;

-- Product companion metadata.
INSERT INTO magento.catalog_eav_attribute (
    attribute_id,
    frontend_input_renderer,
    is_global,
    is_visible,
    is_searchable,
    is_filterable,
    is_comparable,
    is_visible_on_front,
    is_html_allowed_on_front,
    is_used_for_price_rules,
    is_filterable_in_search,
    used_in_product_listing,
    used_for_sort_by,
    apply_to,
    is_visible_in_advanced_search,
    position,
    is_wysiwyg_enabled,
    is_used_for_promo_rules,
    is_required_in_admin_store,
    is_used_in_grid,
    is_visible_in_grid,
    is_filterable_in_grid,
    search_weight,
    is_pagebuilder_enabled,
    additional_data
)
SELECT
    map.seeded_target_attribute_id,
    source.frontend_input_renderer,
    source.is_global,
    source.is_visible,
    source.is_searchable,
    source.is_filterable,
    source.is_comparable,
    source.is_visible_on_front,
    source.is_html_allowed_on_front,
    source.is_used_for_price_rules,
    source.is_filterable_in_search,
    source.used_in_product_listing,
    source.used_for_sort_by,
    source.apply_to,
    source.is_visible_in_advanced_search,
    source.position,
    source.is_wysiwyg_enabled,
    source.is_used_for_promo_rules,
    source.is_required_in_admin_store,
    source.is_used_in_grid,
    source.is_visible_in_grid,
    source.is_filterable_in_grid,
    source.search_weight,
    source.is_pagebuilder_enabled,
    source.additional_data
FROM catalog_eav_attribute source
JOIN ecom7_seeded_attribute_map map
    ON map.source_attribute_id = source.attribute_id
   AND map.entity_type_code = 'catalog_product'
LEFT JOIN magento.catalog_eav_attribute target
    ON target.attribute_id = map.seeded_target_attribute_id
WHERE target.attribute_id IS NULL;

-- Customer companion metadata.
INSERT INTO magento.customer_eav_attribute (
    attribute_id,
    is_visible,
    input_filter,
    multiline_count,
    validate_rules,
    is_system,
    sort_order,
    data_model,
    is_used_in_grid,
    is_visible_in_grid,
    is_filterable_in_grid,
    is_searchable_in_grid,
    grid_filter_condition_type
)
SELECT
    map.seeded_target_attribute_id,
    source.is_visible,
    source.input_filter,
    source.multiline_count,
    source.validate_rules,
    source.is_system,
    source.sort_order,
    source.data_model,
    source.is_used_in_grid,
    source.is_visible_in_grid,
    source.is_filterable_in_grid,
    source.is_searchable_in_grid,
    source.grid_filter_condition_type
FROM customer_eav_attribute source
JOIN ecom7_seeded_attribute_map map
    ON map.source_attribute_id = source.attribute_id
   AND map.entity_type_code = 'customer'
LEFT JOIN magento.customer_eav_attribute target
    ON target.attribute_id = map.seeded_target_attribute_id
WHERE target.attribute_id IS NULL;
