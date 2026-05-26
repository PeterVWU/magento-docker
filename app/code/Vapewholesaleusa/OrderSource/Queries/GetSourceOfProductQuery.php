<?php

namespace Vapewholesaleusa\OrderSource\Queries;

use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

class GetSourceOfProductQuery
{
    /**
     * @var SourceItemRepositoryInterface
     */
    protected SourceItemRepositoryInterface $sourceItems;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected SearchCriteriaBuilderFactory $searchCriteriaBuilder;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;

    /**
     * @var StockItemInterfaceFactory
     */
    protected StockItemInterfaceFactory $stockItemFactory;

    /**
     * @param SourceItemRepositoryInterface $sourceItems
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilder
     * @param ResourceConnection $resourceConnection
     * @param StockItemInterfaceFactory $stockItemFactory
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItems,
        SearchCriteriaBuilderFactory         $searchCriteriaBuilder,
        ResourceConnection            $resourceConnection,
        StockItemInterfaceFactory     $stockItemFactory
    ) {
        $this->sourceItems = $sourceItems;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->stockItemFactory = $stockItemFactory;
    }

    /**
     * @param $productId
     * @param $source
     * @return float|int|null
     */
    public function execute($productId, $source)
    {
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $sql = "SELECT sku FROM $productTable WHERE entity_id = :product_id";
        $sku = $connection->fetchOne($sql, [':product_id' => $productId]);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria = $searchCriteria
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $source)
            ->create();
        $sourceItems = $this->sourceItems->getList($searchCriteria)->getItems();
        /** @var SourceItemInterface $sourceItem */
        $sourceItem = reset($sourceItems);
        $qty = $sourceItem ? $sourceItem?->getQuantity() : 0;

        return $qty;
    }
}
