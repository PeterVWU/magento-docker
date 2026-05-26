<?php

namespace Vapewholesaleusa\OrderSource\Services\SourceArrangement;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySales\Model\GetProductSalableQty;
use Magento\InventoryShippingAdminUi\Ui\DataProvider\GetSourcesByOrderIdSkuAndQty;
use Vapewholesaleusa\OrderSource\Api\ArrangeSourceInterface;
use Vapewholesaleusa\OrderSource\Queries\GetHoldedQtyForSourceSkuQuery;

class ArrangeSourcesByOrderSources implements ArrangeSourceInterface
{
    /**
     * @var GetProductSalableQty
     */
    private $getProductSalableQty;

    /**
     * @var GetSourcesByOrderIdSkuAndQty
     */
    private $getSourcesByOrderIdSkuAndQty;

    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var GetHoldedQtyForSourceSkuQuery
     */
    private $getHoldedQtyForSourceSkuQuery;

    /**
     * ArrangeSourcesByStock constructor.
     * @param GetProductSalableQty $getProductSalableQty
     * @param GetSourcesByOrderIdSkuAndQty $getSourcesByOrderIdSkuAndQty
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param GetHoldedQtyForSourceSkuQuery $getHoldedQtyForSourceSkuQuery
     */
    public function __construct(
        GetProductSalableQty $getProductSalableQty,
        GetSourcesByOrderIdSkuAndQty $getSourcesByOrderIdSkuAndQty,
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        GetHoldedQtyForSourceSkuQuery $getHoldedQtyForSourceSkuQuery
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getSourcesByOrderIdSkuAndQty = $getSourcesByOrderIdSkuAndQty;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->getHoldedQtyForSourceSkuQuery = $getHoldedQtyForSourceSkuQuery;
    }

    /**
     * @param $orderId
     * @param $sku
     * @param $qty
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($orderId, $sku, $qty): array
    {
        $sources = $this->arrangeSources($orderId, $sku, $qty);

        $missedQty = 0;
        for ($i = 0; $i < count($sources); $i++) {
            if($sources[$i]['qtyAvailable'] >= $sources[$i]['qtyToDeduct']) {
                // if qty available is enough to deduct
                // and there is no missed qty end the loop
                if($missedQty == 0) {
                    break;
                }

                // if there is missed qty and qty available is enough cover the missed qty
                // add the missed qty to the source qty to deduct and end the loop
                if($sources[$i]['qtyAvailable'] >= ($sources[$i]['qtyToDeduct'] + $missedQty)) {
                    $sources[$i]['qtyToDeduct'] = $sources[$i]['qtyToDeduct'] + $missedQty;
                    $missedQty = 0;
                    break;
                }

                // if there is missed qty and qty available is not enough to cover all the missed qty
                // deduct the available qty from the missed qty and continue the loop
                $missedQty -= $sources[$i]['qtyAvailable'] - $sources[$i]['qtyToDeduct'];
                $sources[$i]['qtyToDeduct'] = $sources[$i]['qtyAvailable'];
                continue;
            }

            // if qty available is not enough to cover its assigned qty
            // deduct qty deduct to qty available
            $missedQty += $sources[$i]['qtyToDeduct'] - $sources[$i]['qtyAvailable'];
            $sources[$i]['qtyToDeduct'] = $sources[$i]['qtyAvailable'];
        }

        return $sources;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    private function arrangeSources($orderId, $sku, $qty)
    {
        $sources = $this->getSourcesByOrderIdSkuAndQty->execute($orderId, $sku, $qty);
        for ($i = 0; $i < count($sources); $i++) {
            $sources[$i]['qtyAvailable'] -= $this->getHoldedQtyForSourceSkuQuery->execute($sku, $sources[$i]['sourceCode']);
        }

        return $sources;
    }
}
