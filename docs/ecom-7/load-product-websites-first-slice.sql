-- Backfill product-to-website assignments needed for storefront/GraphQL reads.

DROP TABLE IF EXISTS ecom7_stage_catalog_product_website;
CREATE TABLE ecom7_stage_catalog_product_website LIKE catalog_product_website;
INSERT INTO ecom7_stage_catalog_product_website
SELECT website.*
FROM catalog_product_website website
JOIN ecom7_product_sample sample ON sample.product_id = website.product_id;

UPDATE ecom7_stage_catalog_product_website staged
JOIN store_website source_website
    ON source_website.website_id = staged.website_id
JOIN magento.store_website target_website
    ON target_website.code = source_website.code
SET staged.website_id = target_website.website_id;

INSERT INTO magento.catalog_product_website
SELECT staged.*
FROM ecom7_stage_catalog_product_website staged
LEFT JOIN magento.catalog_product_website target
    ON target.product_id = staged.product_id
   AND target.website_id = staged.website_id
WHERE target.product_id IS NULL;

SELECT COUNT(*) AS product_website_rows
FROM magento.catalog_product_website
WHERE product_id IN (SELECT product_id FROM ecom7_product_sample);
