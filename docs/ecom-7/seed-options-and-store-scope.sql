-- Seed option rows and store scope needed by the first rehearsal slice.

DROP TABLE IF EXISTS ecom7_seeded_option_map;
CREATE TABLE ecom7_seeded_option_map (
    source_option_id INT UNSIGNED NOT NULL PRIMARY KEY,
    attribute_code VARCHAR(255) NOT NULL,
    option_label VARCHAR(255) NULL,
    target_option_id INT UNSIGNED NOT NULL
);

DROP PROCEDURE IF EXISTS ecom7_seed_options;
DELIMITER //
CREATE PROCEDURE ecom7_seed_options()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE source_option_id_value INT UNSIGNED;
    DECLARE target_attribute_id_value SMALLINT UNSIGNED;
    DECLARE attribute_code_value VARCHAR(255);
    DECLARE option_label_value VARCHAR(255);
    DECLARE sort_order_value SMALLINT UNSIGNED;
    DECLARE target_option_id_value INT UNSIGNED;

    DECLARE option_cursor CURSOR FOR
        SELECT
            source_option.option_id,
            attribute_map.seeded_target_attribute_id,
            source_attribute.attribute_code,
            source_value.value,
            source_option.sort_order
        FROM eav_attribute_option source_option
        JOIN ecom7_attribute_option_sample sample
            ON sample.option_id = source_option.option_id
        JOIN eav_attribute source_attribute
            ON source_attribute.attribute_id = source_option.attribute_id
        JOIN eav_attribute_option_value source_value
            ON source_value.option_id = source_option.option_id
           AND source_value.store_id = 0
        JOIN ecom7_seeded_attribute_map attribute_map
            ON attribute_map.source_attribute_id = source_option.attribute_id
        ORDER BY source_option.option_id;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN option_cursor;

    read_loop: LOOP
        FETCH option_cursor INTO
            source_option_id_value,
            target_attribute_id_value,
            attribute_code_value,
            option_label_value,
            sort_order_value;

        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SELECT MAX(target_option.option_id)
        INTO target_option_id_value
        FROM magento.eav_attribute_option target_option
        JOIN magento.eav_attribute_option_value target_value
            ON target_value.option_id = target_option.option_id
           AND target_value.store_id = 0
        WHERE target_option.attribute_id = target_attribute_id_value
          AND target_value.value = option_label_value;

        IF target_option_id_value IS NULL THEN
            INSERT INTO magento.eav_attribute_option (attribute_id, sort_order)
            VALUES (target_attribute_id_value, sort_order_value);

            SET target_option_id_value = LAST_INSERT_ID();

            INSERT INTO magento.eav_attribute_option_value (option_id, store_id, value)
            VALUES (target_option_id_value, 0, option_label_value);
        END IF;

        INSERT INTO ecom7_seeded_option_map (
            source_option_id,
            attribute_code,
            option_label,
            target_option_id
        ) VALUES (
            source_option_id_value,
            attribute_code_value,
            option_label_value,
            target_option_id_value
        );

        SET target_option_id_value = NULL;
    END LOOP;

    CLOSE option_cursor;
END//
DELIMITER ;

CALL ecom7_seed_options();
DROP PROCEDURE ecom7_seed_options;

-- Store scope: seed Nichero website/group/store, preserve semantic keys.
INSERT INTO magento.store_website (code, name, sort_order, default_group_id, is_default)
SELECT source.code, source.name, source.sort_order, 0, 0
FROM store_website source
LEFT JOIN magento.store_website target ON target.code = source.code
WHERE source.website_id IN (1, 2, 3)
  AND target.website_id IS NULL;

INSERT INTO magento.store_group (website_id, name, root_category_id, default_store_id, code)
SELECT
    target_website.website_id,
    source.name,
    2,
    0,
    source.code
FROM store_group source
JOIN magento.store_website target_website ON target_website.code = (
    SELECT source_website.code
    FROM store_website source_website
    WHERE source_website.website_id = source.website_id
)
LEFT JOIN magento.store_group target ON target.code = source.code
WHERE source.group_id IN (1, 2, 3)
  AND target.group_id IS NULL;

INSERT INTO magento.store (code, website_id, group_id, name, sort_order, is_active)
SELECT
    source.code,
    target_website.website_id,
    target_group.group_id,
    source.name,
    source.sort_order,
    source.is_active
FROM store source
JOIN store_website source_website ON source_website.website_id = source.website_id
JOIN magento.store_website target_website ON target_website.code = source_website.code
JOIN store_group source_group ON source_group.group_id = source.group_id
JOIN magento.store_group target_group ON target_group.code = source_group.code
LEFT JOIN magento.store target ON target.code = source.code
WHERE source.store_id IN (1, 2, 3)
  AND target.store_id IS NULL;

UPDATE magento.store_group target_group
JOIN store_group source_group ON source_group.code = target_group.code
JOIN store source_store ON source_store.store_id = source_group.default_store_id
JOIN magento.store target_store ON target_store.code = source_store.code
SET target_group.default_store_id = target_store.store_id
WHERE source_group.group_id IN (1, 2, 3);

UPDATE magento.store_website target_website
JOIN store_website source_website ON source_website.code = target_website.code
JOIN store_group source_group ON source_group.group_id = source_website.default_group_id
JOIN magento.store_group target_group ON target_group.code = source_group.code
SET target_website.default_group_id = target_group.group_id
WHERE source_website.website_id IN (1, 2, 3);

-- MSI exists only in the target build. Link seeded websites to Default Stock so
-- target-side search/inventory indexes can resolve their sales channels.
INSERT INTO magento.inventory_stock_sales_channel (type, code, stock_id)
SELECT 'website', target_website.code, 1
FROM magento.store_website target_website
LEFT JOIN magento.inventory_stock_sales_channel channel
    ON channel.type = 'website'
   AND channel.code = target_website.code
WHERE target_website.code IN ('base', 'nichero_website', 'vapeguysinc_website')
  AND channel.code IS NULL;

SELECT COUNT(*) AS seeded_options
FROM ecom7_seeded_option_map;

SELECT website_id, code, name
FROM magento.store_website
ORDER BY website_id;

SELECT group_id, website_id, code, name, root_category_id
FROM magento.store_group
ORDER BY group_id;

SELECT store_id, code, website_id, group_id, name
FROM magento.store
ORDER BY store_id;
