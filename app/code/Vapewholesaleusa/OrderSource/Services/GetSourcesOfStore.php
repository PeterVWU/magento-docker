<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventorySales\Model\ResourceModel\StockIdResolver;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;


class GetSourcesOfStore
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var StockIdResolver
     */
    private $stockResolver;


    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param StockIdResolver $stockResolver
     */
    public function __construct(
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        LoggerInterface              $logger,
        StoreManagerInterface        $storeManager,
        GetStockSourceLinksInterface $getStockSourceLinks,
        StockIdResolver                $stockResolver
    )
    {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\StockSourceLinkInterface[]|array
     */
    public function execute()
    {
        $sources = [];
        try {
            $stockId = $this->stockResolver->resolve(
                SalesChannelInterface::TYPE_WEBSITE,
                $this->storeManager->getStore()->getCode()
            );

            if(!$stockId) {
                $stockId = $this->stockResolver->resolve(
                    SalesChannelInterface::TYPE_WEBSITE,
                    'base'
                );
            }

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
                ->create();
            $sources = $this->getStockSourceLinks->execute($searchCriteria)->getItems();

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $sources;
    }

}
