-- ECOM-77 retained extension-data source profile.
--
-- Run in the legacy source schema after the ECOM-7 sample helper tables exist.
-- This script intentionally reports counts and dependencies only; it does not
-- export or mutate data.

SELECT 'salesrep_rows_in_sample' AS metric, COUNT(*) AS row_count
FROM salesrep rep
JOIN ecom7_order_sample sample ON sample.order_id = rep.order_id
UNION ALL
SELECT 'salesrep_rows_total', COUNT(*)
FROM salesrep;

SELECT
    'salesrep_commission_group_table_exists' AS metric,
    COUNT(*) AS row_count
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'salesrep_commission_group';

SET @salesrep_commission_group_count_sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'salesrep_commission_group'
    ),
    'SELECT ''salesrep_commission_groups_total'' AS metric, COUNT(*) AS row_count FROM salesrep_commission_group',
    'SELECT ''salesrep_commission_groups_total'' AS metric, 0 AS row_count'
);
PREPARE salesrep_commission_group_count_stmt
FROM @salesrep_commission_group_count_sql;
EXECUTE salesrep_commission_group_count_stmt;
DEALLOCATE PREPARE salesrep_commission_group_count_stmt;

SELECT
    'salesrep_missing_order' AS check_name,
    COUNT(*) AS row_count
FROM salesrep rep
LEFT JOIN sales_order orders ON orders.entity_id = rep.order_id
WHERE orders.entity_id IS NULL;

SELECT
    'admin_user_table_exists' AS metric,
    COUNT(*) AS row_count
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'admin_user';

SET @salesrep_missing_admin_sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'admin_user'
    ),
    'SELECT ''salesrep_missing_rep_admin'' AS check_name, COUNT(*) AS row_count FROM salesrep rep LEFT JOIN admin_user admin ON admin.user_id = rep.rep_id WHERE rep.rep_id IS NOT NULL AND rep.rep_id <> 0 AND admin.user_id IS NULL',
    'SELECT ''salesrep_missing_rep_admin'' AS check_name, COUNT(*) AS row_count FROM salesrep rep WHERE rep.rep_id IS NOT NULL AND rep.rep_id <> 0'
);
PREPARE salesrep_missing_admin_stmt
FROM @salesrep_missing_admin_sql;
EXECUTE salesrep_missing_admin_stmt;
DEALLOCATE PREPARE salesrep_missing_admin_stmt;

SELECT
    'ordersource_rows_in_sample' AS metric,
    COUNT(*) AS row_count
FROM vapewholesaleusa_ordersource_ordersource source
JOIN ecom7_order_sample sample ON sample.order_id = source.order_id
UNION ALL
SELECT 'ordersource_rows_total', COUNT(*)
FROM vapewholesaleusa_ordersource_ordersource;

SELECT
    'ordersource_missing_order' AS check_name,
    COUNT(*) AS row_count
FROM vapewholesaleusa_ordersource_ordersource source
LEFT JOIN sales_order orders ON orders.entity_id = source.order_id
WHERE source.order_id IS NOT NULL
  AND orders.entity_id IS NULL
UNION ALL
SELECT
    'ordersource_missing_order_item',
    COUNT(*)
FROM vapewholesaleusa_ordersource_ordersource source
LEFT JOIN sales_order_item item ON item.item_id = source.item_id
WHERE source.item_id IS NOT NULL
  AND item.item_id IS NULL;

SELECT
    attr.attribute_code,
    COUNT(value.value_id) AS non_null_values
FROM eav_attribute attr
JOIN eav_entity_type type ON type.entity_type_id = attr.entity_type_id
LEFT JOIN customer_entity_datetime value
    ON value.attribute_id = attr.attribute_id
   AND value.value IS NOT NULL
WHERE type.entity_type_code = 'customer'
  AND attr.attribute_code IN (
      'business_license_expiration',
      'tobacco_license_expiration'
  )
GROUP BY attr.attribute_code
ORDER BY attr.attribute_code;

SELECT
    'customer_attachment_table_exists' AS metric,
    COUNT(*) AS row_count
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'customer_attachment';

SET @customer_attachment_count_sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'customer_attachment'
    ),
    'SELECT ''customer_attachment_rows_total'' AS metric, COUNT(*) AS row_count FROM customer_attachment',
    'SELECT ''customer_attachment_rows_total'' AS metric, 0 AS row_count'
);
PREPARE customer_attachment_count_stmt
FROM @customer_attachment_count_sql;
EXECUTE customer_attachment_count_stmt;
DEALLOCATE PREPARE customer_attachment_count_stmt;

SET @customer_attachment_missing_customer_sql = IF(
    EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'customer_attachment'
    ),
    'SELECT ''customer_attachment_missing_customer'' AS metric, COUNT(*) AS row_count FROM customer_attachment attachment LEFT JOIN customer_entity customer ON customer.entity_id = attachment.customer_id WHERE customer.entity_id IS NULL',
    'SELECT ''customer_attachment_missing_customer'' AS metric, 0 AS row_count'
);
PREPARE customer_attachment_missing_customer_stmt
FROM @customer_attachment_missing_customer_sql;
EXECUTE customer_attachment_missing_customer_stmt;
DEALLOCATE PREPARE customer_attachment_missing_customer_stmt;

SELECT
    'store_credit_orders_in_sample' AS metric,
    COUNT(*) AS row_count
FROM sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
WHERE orders.amstorecredit_amount IS NOT NULL
  AND orders.amstorecredit_amount <> 0
UNION ALL
SELECT
    'reward_orders_in_sample',
    COUNT(*)
FROM sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
WHERE orders.aw_reward_points IS NOT NULL
  AND orders.aw_reward_points <> 0;

SELECT
    'payment_request_candidate_orders' AS metric,
    COUNT(*) AS row_count
FROM sales_order orders
JOIN ecom7_order_sample sample ON sample.order_id = orders.entity_id
JOIN sales_order_payment payment ON payment.parent_id = orders.entity_id
WHERE payment.method = 'mageworx_ordereditor_payment_method';
