-- ECOM-7 first production-derived order slice.
--
-- Window: one month before the 2026-04-03 dump date.
-- Use an exclusive upper bound so the query is stable if the dump contains
-- partial April 3 data.

SET @window_start = '2026-03-03 00:00:00';
SET @window_end = '2026-04-03 00:00:00';

DROP TABLE IF EXISTS ecom7_order_sample;
CREATE TABLE ecom7_order_sample (
    order_id INT UNSIGNED NOT NULL PRIMARY KEY,
    sample_reason VARCHAR(64) NOT NULL
);

-- Business-critical retained behavior first.
INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason)
SELECT entity_id, 'reward_points'
FROM sales_order
WHERE created_at >= @window_start
  AND created_at < @window_end
  AND aw_reward_points IS NOT NULL
  AND aw_reward_points <> 0
ORDER BY created_at DESC, entity_id DESC
LIMIT 5;

INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason)
SELECT entity_id, 'store_credit'
FROM sales_order
WHERE created_at >= @window_start
  AND created_at < @window_end
  AND amstorecredit_amount IS NOT NULL
  AND amstorecredit_amount <> 0
ORDER BY created_at DESC, entity_id DESC
LIMIT 10;

INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason)
SELECT s.order_id, 'salesrep'
FROM salesrep s
JOIN sales_order o ON o.entity_id = s.order_id
WHERE o.created_at >= @window_start
  AND o.created_at < @window_end
ORDER BY o.created_at DESC, o.entity_id DESC
LIMIT 10;

-- Operational states next.
INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason)
SELECT entity_id, 'canceled'
FROM sales_order
WHERE created_at >= @window_start
  AND created_at < @window_end
  AND status = 'canceled'
ORDER BY created_at DESC, entity_id DESC
LIMIT 5;

INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason)
SELECT entity_id, 'closed'
FROM sales_order
WHERE created_at >= @window_start
  AND created_at < @window_end
  AND status = 'closed'
ORDER BY created_at DESC, entity_id DESC
LIMIT 5;

INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason)
SELECT entity_id, 'processing'
FROM sales_order
WHERE created_at >= @window_start
  AND created_at < @window_end
  AND status = 'processing'
ORDER BY created_at DESC, entity_id DESC
LIMIT 5;

INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason)
SELECT entity_id, 'verification_or_pending'
FROM sales_order
WHERE created_at >= @window_start
  AND created_at < @window_end
  AND status IN ('order_verification_required', 'pending', 'pending_payment')
ORDER BY created_at DESC, entity_id DESC
LIMIT 5;

-- Fill the remainder with recent complete orders.
SET @remaining_sample_slots = 50 - (SELECT COUNT(*) FROM ecom7_order_sample);
SET @complete_fill_sql = CONCAT(
    'INSERT IGNORE INTO ecom7_order_sample (order_id, sample_reason) ',
    'SELECT entity_id, ''complete_fill'' ',
    'FROM sales_order ',
    'WHERE created_at >= ''', @window_start, ''' ',
    'AND created_at < ''', @window_end, ''' ',
    'AND status = ''complete'' ',
    'ORDER BY created_at DESC, entity_id DESC ',
    'LIMIT ',
    GREATEST(@remaining_sample_slots, 0)
);
PREPARE complete_fill_stmt FROM @complete_fill_sql;
EXECUTE complete_fill_stmt;
DEALLOCATE PREPARE complete_fill_stmt;

SELECT
    sample_reason,
    COUNT(*) AS order_count
FROM ecom7_order_sample
GROUP BY sample_reason
ORDER BY sample_reason;

SELECT
    o.entity_id,
    o.increment_id,
    o.created_at,
    o.status,
    o.customer_id,
    o.amstorecredit_amount,
    o.aw_reward_points,
    EXISTS (
        SELECT 1
        FROM salesrep s
        WHERE s.order_id = o.entity_id
    ) AS has_salesrep,
    sample.sample_reason
FROM ecom7_order_sample sample
JOIN sales_order o ON o.entity_id = sample.order_id
ORDER BY o.created_at DESC, o.entity_id DESC;
