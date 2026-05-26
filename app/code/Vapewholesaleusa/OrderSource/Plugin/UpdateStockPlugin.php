<?php

namespace Vapewholesaleusa\OrderSource\Plugin;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Services\GetClientSourceService;
use Vapewholesaleusa\OrderSource\Services\UpdateSourceBySku;

/**
 * Class UpdateStockPlugin
 */
class UpdateStockPlugin
{

    /**
     * @var UpdateSourceBySku
     */
    private $updateSourceBySku;

    /**
     * @var GetClientSourceService
     */
    private $getClientSourceService;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param UpdateSourceBySku $updateSourceBySku
     * @param GetClientSourceService $getClientSourceService
     * @param Data $helper
     */
    public function __construct(
        UpdateSourceBySku $updateSourceBySku,
        GetClientSourceService $getClientSourceService,
        Data $helper
    ) {
        $this->updateSourceBySku = $updateSourceBySku;
        $this->getClientSourceService = $getClientSourceService;
        $this->helper = $helper;
    }

    /**
     * @param StockRegistry $subject
     * @param callable $proceed
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return null
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException|\Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundUpdateStockItemBySku(StockRegistry $subject, callable $proceed, $productSku, StockItemInterface $stockItem)
    {
        if(!$this->helper->isModuleEnabled()) {
            return $proceed($productSku, $stockItem);
        }

        if(!$this->helper->getStockSourcing()) {
            return $proceed($productSku, $stockItem);
        }

        $sourceCode = $this->getClientSourceService->execute();
        if($sourceCode) {
            return $this->updateSourceBySku->execute($productSku, $sourceCode, $stockItem->getQty());
        }

        return $proceed($productSku, $stockItem);
    }
}
