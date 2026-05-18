-- Seed the category ancestor closure required by the first rehearsal slice.

DROP TABLE IF EXISTS ecom7_category_closure;
CREATE TABLE ecom7_category_closure AS
WITH RECURSIVE category_closure AS (
    SELECT category.entity_id, category.parent_id, category.level
    FROM catalog_category_entity category
    JOIN ecom7_category_sample sample ON sample.category_id = category.entity_id
    UNION DISTINCT
    SELECT parent.entity_id, parent.parent_id, parent.level
    FROM catalog_category_entity parent
    JOIN category_closure child ON child.parent_id = parent.entity_id
)
SELECT entity_id AS source_category_id, parent_id AS source_parent_id, level
FROM category_closure;

DROP TABLE IF EXISTS ecom7_seeded_category_map;
CREATE TABLE ecom7_seeded_category_map (
    source_category_id INT UNSIGNED NOT NULL PRIMARY KEY,
    target_category_id INT UNSIGNED NOT NULL,
    source_parent_id INT UNSIGNED NOT NULL,
    target_parent_id INT UNSIGNED NOT NULL,
    level INT NOT NULL
);

INSERT INTO ecom7_seeded_category_map (
    source_category_id,
    target_category_id,
    source_parent_id,
    target_parent_id,
    level
) VALUES
    (1, 1, 0, 0, 0),
    (2, 2, 1, 1, 1);

DROP PROCEDURE IF EXISTS ecom7_seed_category_closure;
DELIMITER //
CREATE PROCEDURE ecom7_seed_category_closure()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE source_category_id_value INT UNSIGNED;
    DECLARE source_parent_id_value INT UNSIGNED;
    DECLARE source_attribute_set_id_value SMALLINT UNSIGNED;
    DECLARE source_created_at_value TIMESTAMP;
    DECLARE source_updated_at_value TIMESTAMP;
    DECLARE source_position_value INT;
    DECLARE source_level_value INT;
    DECLARE source_children_count_value INT;
    DECLARE target_parent_id_value INT UNSIGNED;
    DECLARE target_parent_path_value VARCHAR(255);
    DECLARE target_category_id_value INT UNSIGNED;

    DECLARE category_cursor CURSOR FOR
        SELECT
            source.entity_id,
            source.parent_id,
            source.attribute_set_id,
            source.created_at,
            source.updated_at,
            source.position,
            source.level,
            source.children_count
        FROM catalog_category_entity source
        JOIN ecom7_category_closure closure
            ON closure.source_category_id = source.entity_id
        WHERE source.entity_id NOT IN (1, 2)
        ORDER BY source.level, source.position, source.entity_id;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN category_cursor;

    read_loop: LOOP
        FETCH category_cursor INTO
            source_category_id_value,
            source_parent_id_value,
            source_attribute_set_id_value,
            source_created_at_value,
            source_updated_at_value,
            source_position_value,
            source_level_value,
            source_children_count_value;

        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SELECT target_category_id, target_category.path
        INTO target_parent_id_value, target_parent_path_value
        FROM ecom7_seeded_category_map parent_map
        JOIN magento.catalog_category_entity target_category
            ON target_category.entity_id = parent_map.target_category_id
        WHERE parent_map.source_category_id = source_parent_id_value;

        INSERT INTO magento.catalog_category_entity (
            attribute_set_id,
            parent_id,
            created_at,
            updated_at,
            path,
            position,
            level,
            children_count
        ) VALUES (
            source_attribute_set_id_value,
            target_parent_id_value,
            source_created_at_value,
            source_updated_at_value,
            '',
            source_position_value,
            source_level_value,
            source_children_count_value
        );

        SET target_category_id_value = LAST_INSERT_ID();

        UPDATE magento.catalog_category_entity
        SET path = CONCAT(target_parent_path_value, '/', target_category_id_value)
        WHERE entity_id = target_category_id_value;

        INSERT INTO ecom7_seeded_category_map (
            source_category_id,
            target_category_id,
            source_parent_id,
            target_parent_id,
            level
        ) VALUES (
            source_category_id_value,
            target_category_id_value,
            source_parent_id_value,
            target_parent_id_value,
            source_level_value
        );
    END LOOP;

    CLOSE category_cursor;
END//
DELIMITER ;

CALL ecom7_seed_category_closure();
DROP PROCEDURE ecom7_seed_category_closure;

DROP TABLE IF EXISTS ecom7_store_map;
CREATE TABLE ecom7_store_map AS
SELECT
    source.store_id AS source_store_id,
    target.store_id AS target_store_id
FROM store source
JOIN magento.store target ON target.code = source.code
WHERE source.store_id IN (0, 1, 2, 3);

INSERT INTO magento.catalog_category_entity_datetime (attribute_id, store_id, entity_id, value)
SELECT target_attribute.attribute_id, store_map.target_store_id, category_map.target_category_id, source_value.value
FROM catalog_category_entity_datetime source_value
JOIN ecom7_seeded_category_map category_map
    ON category_map.source_category_id = source_value.entity_id
JOIN eav_attribute source_attribute
    ON source_attribute.attribute_id = source_value.attribute_id
JOIN magento.eav_attribute target_attribute
    ON target_attribute.attribute_code = source_attribute.attribute_code
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = source_value.store_id
WHERE category_map.source_category_id NOT IN (1, 2)
  AND target_attribute.entity_type_id = (
      SELECT entity_type_id
      FROM magento.eav_entity_type
      WHERE entity_type_code = 'catalog_category'
  );

INSERT INTO magento.catalog_category_entity_decimal (attribute_id, store_id, entity_id, value)
SELECT target_attribute.attribute_id, store_map.target_store_id, category_map.target_category_id, source_value.value
FROM catalog_category_entity_decimal source_value
JOIN ecom7_seeded_category_map category_map
    ON category_map.source_category_id = source_value.entity_id
JOIN eav_attribute source_attribute
    ON source_attribute.attribute_id = source_value.attribute_id
JOIN magento.eav_attribute target_attribute
    ON target_attribute.attribute_code = source_attribute.attribute_code
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = source_value.store_id
WHERE category_map.source_category_id NOT IN (1, 2)
  AND target_attribute.entity_type_id = (
      SELECT entity_type_id
      FROM magento.eav_entity_type
      WHERE entity_type_code = 'catalog_category'
  );

INSERT INTO magento.catalog_category_entity_int (attribute_id, store_id, entity_id, value)
SELECT target_attribute.attribute_id, store_map.target_store_id, category_map.target_category_id, source_value.value
FROM catalog_category_entity_int source_value
JOIN ecom7_seeded_category_map category_map
    ON category_map.source_category_id = source_value.entity_id
JOIN eav_attribute source_attribute
    ON source_attribute.attribute_id = source_value.attribute_id
JOIN magento.eav_attribute target_attribute
    ON target_attribute.attribute_code = source_attribute.attribute_code
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = source_value.store_id
WHERE category_map.source_category_id NOT IN (1, 2)
  AND target_attribute.entity_type_id = (
      SELECT entity_type_id
      FROM magento.eav_entity_type
      WHERE entity_type_code = 'catalog_category'
  );

INSERT INTO magento.catalog_category_entity_text (attribute_id, store_id, entity_id, value)
SELECT target_attribute.attribute_id, store_map.target_store_id, category_map.target_category_id, source_value.value
FROM catalog_category_entity_text source_value
JOIN ecom7_seeded_category_map category_map
    ON category_map.source_category_id = source_value.entity_id
JOIN eav_attribute source_attribute
    ON source_attribute.attribute_id = source_value.attribute_id
JOIN magento.eav_attribute target_attribute
    ON target_attribute.attribute_code = source_attribute.attribute_code
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = source_value.store_id
WHERE category_map.source_category_id NOT IN (1, 2)
  AND target_attribute.entity_type_id = (
      SELECT entity_type_id
      FROM magento.eav_entity_type
      WHERE entity_type_code = 'catalog_category'
  );

INSERT INTO magento.catalog_category_entity_varchar (attribute_id, store_id, entity_id, value)
SELECT target_attribute.attribute_id, store_map.target_store_id, category_map.target_category_id, source_value.value
FROM catalog_category_entity_varchar source_value
JOIN ecom7_seeded_category_map category_map
    ON category_map.source_category_id = source_value.entity_id
JOIN eav_attribute source_attribute
    ON source_attribute.attribute_id = source_value.attribute_id
JOIN magento.eav_attribute target_attribute
    ON target_attribute.attribute_code = source_attribute.attribute_code
JOIN ecom7_store_map store_map
    ON store_map.source_store_id = source_value.store_id
WHERE category_map.source_category_id NOT IN (1, 2)
  AND target_attribute.entity_type_id = (
      SELECT entity_type_id
      FROM magento.eav_entity_type
      WHERE entity_type_code = 'catalog_category'
  );

SELECT COUNT(*) AS closure_categories
FROM ecom7_category_closure;

SELECT COUNT(*) AS mapped_categories
FROM ecom7_seeded_category_map;

SELECT
    map.source_category_id,
    map.target_category_id,
    source.path AS source_path,
    target.path AS target_path
FROM ecom7_seeded_category_map map
JOIN catalog_category_entity source
    ON source.entity_id = map.source_category_id
JOIN magento.catalog_category_entity target
    ON target.entity_id = map.target_category_id
ORDER BY map.level, map.source_category_id;
