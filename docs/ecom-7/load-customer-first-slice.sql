-- Load customers and customer addresses for the first rehearsal slice.

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

DROP TABLE IF EXISTS ecom7_stage_customer_entity;
CREATE TABLE ecom7_stage_customer_entity LIKE customer_entity;
INSERT INTO ecom7_stage_customer_entity
SELECT customer.*
FROM customer_entity customer
JOIN ecom7_customer_sample sample ON sample.customer_id = customer.entity_id
LEFT JOIN magento.customer_entity target_customer
    ON target_customer.entity_id = customer.entity_id
WHERE target_customer.entity_id IS NULL;

UPDATE ecom7_stage_customer_entity staged_customer
JOIN ecom7_customer_group_map group_map
    ON group_map.source_customer_group_id = staged_customer.group_id
JOIN store source_store
    ON source_store.store_id = staged_customer.store_id
JOIN store_website source_website
    ON source_website.website_id = staged_customer.website_id
JOIN magento.store target_store
    ON target_store.code = source_store.code
JOIN magento.store_website target_website
    ON target_website.code = source_website.code
SET
    staged_customer.group_id = group_map.target_customer_group_id,
    staged_customer.store_id = target_store.store_id,
    staged_customer.website_id = target_website.website_id;

CALL ecom7_insert_common_columns(
    'ecom7_stage_customer_entity',
    'customer_entity'
);

DROP TABLE IF EXISTS ecom7_stage_customer_address_entity;
CREATE TABLE ecom7_stage_customer_address_entity LIKE customer_address_entity;
INSERT INTO ecom7_stage_customer_address_entity
SELECT address.*
FROM customer_address_entity address
JOIN ecom7_customer_address_sample sample ON sample.entity_id = address.entity_id
LEFT JOIN magento.customer_address_entity target_address
    ON target_address.entity_id = address.entity_id
WHERE target_address.entity_id IS NULL;

CALL ecom7_insert_common_columns(
    'ecom7_stage_customer_address_entity',
    'customer_address_entity'
);

DROP TABLE IF EXISTS ecom7_stage_customer_entity_datetime;
CREATE TABLE ecom7_stage_customer_entity_datetime LIKE customer_entity_datetime;
INSERT INTO ecom7_stage_customer_entity_datetime
SELECT value.*
FROM customer_entity_datetime value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_customer_entity_decimal;
CREATE TABLE ecom7_stage_customer_entity_decimal LIKE customer_entity_decimal;
INSERT INTO ecom7_stage_customer_entity_decimal
SELECT value.*
FROM customer_entity_decimal value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_customer_entity_int;
CREATE TABLE ecom7_stage_customer_entity_int LIKE customer_entity_int;
INSERT INTO ecom7_stage_customer_entity_int
SELECT value.*
FROM customer_entity_int value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_customer_entity_text;
CREATE TABLE ecom7_stage_customer_entity_text LIKE customer_entity_text;
INSERT INTO ecom7_stage_customer_entity_text
SELECT value.*
FROM customer_entity_text value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id;

DROP TABLE IF EXISTS ecom7_stage_customer_entity_varchar;
CREATE TABLE ecom7_stage_customer_entity_varchar LIKE customer_entity_varchar;
INSERT INTO ecom7_stage_customer_entity_varchar
SELECT value.*
FROM customer_entity_varchar value
JOIN ecom7_customer_sample sample ON sample.customer_id = value.entity_id;

UPDATE ecom7_stage_customer_entity_datetime staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'customer'
SET staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000;
UPDATE ecom7_stage_customer_entity_datetime
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_customer_entity_decimal staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'customer'
SET staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000;
UPDATE ecom7_stage_customer_entity_decimal
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_customer_entity_int staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'customer'
SET staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000;
UPDATE ecom7_stage_customer_entity_int
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_customer_entity_text staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'customer'
SET staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000;
UPDATE ecom7_stage_customer_entity_text
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

UPDATE ecom7_stage_customer_entity_varchar staged_value
JOIN ecom7_seeded_attribute_map attribute_map
    ON attribute_map.source_attribute_id = staged_value.attribute_id
   AND attribute_map.entity_type_code = 'customer'
SET staged_value.attribute_id = attribute_map.seeded_target_attribute_id + 10000;
UPDATE ecom7_stage_customer_entity_varchar
SET attribute_id = attribute_id - 10000
WHERE attribute_id >= 10000;

CALL ecom7_insert_common_columns(
    'ecom7_stage_customer_entity_datetime',
    'customer_entity_datetime'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_customer_entity_decimal',
    'customer_entity_decimal'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_customer_entity_int',
    'customer_entity_int'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_customer_entity_text',
    'customer_entity_text'
);
CALL ecom7_insert_common_columns(
    'ecom7_stage_customer_entity_varchar',
    'customer_entity_varchar'
);

DROP PROCEDURE ecom7_insert_common_columns;

SELECT 'customer_entity' AS domain, COUNT(*) AS row_count
FROM magento.customer_entity
WHERE entity_id IN (SELECT customer_id FROM ecom7_customer_sample)
UNION ALL
SELECT 'customer_address_entity', COUNT(*)
FROM magento.customer_address_entity
WHERE entity_id IN (SELECT entity_id FROM ecom7_customer_address_sample)
UNION ALL
SELECT 'customer_entity_int', COUNT(*)
FROM magento.customer_entity_int
WHERE entity_id IN (SELECT customer_id FROM ecom7_customer_sample)
UNION ALL
SELECT 'customer_entity_varchar', COUNT(*)
FROM magento.customer_entity_varchar
WHERE entity_id IN (SELECT customer_id FROM ecom7_customer_sample);
