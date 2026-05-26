<?php

namespace Vapewholesaleusa\OrderSource\Plugin;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockRegistryProvider;
use Magento\Framework\App\Request\Http;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Queries\GetSourceOfProductQuery;
use Vapewholesaleusa\OrderSource\Services\GetClientSourceService;

/**
 * Class GetStockItemAfterPlugin
 */
class GetStockItemAfterPlugin
{
    /**
     * @var GetSourceOfProductQuery
     */
    protected GetSourceOfProductQuery $getSourceOfProductQuery;

    /**
     * @var GetClientSourceService
     */
    protected GetClientSourceService $getClientSourceService;

    /**
     * @var Http
     */
    protected Http $request;

    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @param GetSourceOfProductQuery $getSourceOfProductQuery
     * @param GetClientSourceService $getClientSourceService
     * @param Http $request
     * @param Data $helper
     */
    public function __construct(
        GetSourceOfProductQuery $getSourceOfProductQuery,
        GetClientSourceService $getClientSourceService,
        Http $request,
        Data $helper
    ) {
        $this->getSourceOfProductQuery = $getSourceOfProductQuery;
        $this->getClientSourceService = $getClientSourceService;
        $this->request = $request;
        $this->helper = $helper;
    }

    /**
     * @param StockRegistryProvider $subject
     * @param $stockItem
     * @return StockItemInterface
     */
    public function afterGetStockItem(StockRegistryProvider $subject, $stockItem)
    {
        if(!$this->helper->isModuleEnabled()) {
            return $stockItem;
        }

        if(!$this->helper->getStockSourcing()) {
            return $stockItem;
        }

        $source = $this->getClientSourceService->execute();
        if($source && $this->request->getMethod() == 'GET') {
            $productId = $stockItem->getProductId();
            $newStockItem = $this->getSourceOfProductQuery->execute($productId, $source);
            $stockItem->setQty($newStockItem ?? 0);
        }

        return $stockItem;
    }
}
