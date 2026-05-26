<?php

namespace Vapewholesaleusa\OrderSource\Services\SourceArrangement;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySales\Model\GetProductSalableQty;
use Magento\InventoryShippingAdminUi\Ui\DataProvider\GetSourcesByOrderIdSkuAndQty;
use Vapewholesaleusa\OrderSource\Api\ArrangeSourceInterface;

class ArrangeSourcesByStock implements ArrangeSourceInterface
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
     * ArrangeSourcesByStock constructor.
     * @param GetProductSalableQty $getProductSalableQty
     * @param GetSourcesByOrderIdSkuAndQty $getSourcesByOrderIdSkuAndQty
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     */
    public function __construct(
        GetProductSalableQty $getProductSalableQty,
        GetSourcesByOrderIdSkuAndQty $getSourcesByOrderIdSkuAndQty,
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getSourcesByOrderIdSkuAndQty = $getSourcesByOrderIdSkuAndQty;
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
    }

    /**
     * @param $orderId
     * @param $sku
     * @param $qty
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute($orderId, $sku, $qty): array
    {
        $sources = $this->getSourcesByOrderIdSkuAndQty->execute($orderId, $sku, $qty);

        $productSallableQty = $this->getProductSalableQty->execute($sku, $this->getStockIdForCurrentWebsite->execute()) + $qty;
        $totalSourcesQty = array_reduce($sources, function ($carry, $item) {
            return $carry + $item['qtyAvailable'];
        }, 0);
        $substractQty = (float)$totalSourcesQty - (float)$productSallableQty;

        if ($substractQty == 0) {
            return $sources;
        }

        $missedQty = 0;
        for ($i = 0; $i < count($sources); $i++) {
            //realavailable qty is the qty after deducting the qty that is not deducted from sources
            $realAvailableQty = $sources[$i]['qtyAvailable'] - $substractQty;
            //real qty is the qty after adding the qtyToDeduct to the qty that is not deducted from sources
            $totalQty = $missedQty + $sources[$i]['qtyToDeduct'];
            //it is the difference between total sources qty and magento stock qty
            //we will not need it if we have enough qty in sources
            $substractQty = 0;

            //If real available qty is greater than or equal to total qty
            //then we will deduct the total qty from the source and break the loop
            //because we succesfully assigned all the qty to sources
            if ($realAvailableQty >= $totalQty) {
                $sources[$i]['qtyToDeduct'] = $totalQty;
                break;
            }

            //If real available qty is greater than qty to deduct
            //then we will increase the qty to deduct to the real available qty
            //and check if still we have missed qty, continue if we have missed qty or break the loop
            if ($realAvailableQty > $sources[$i]['qtyToDeduct']) {
                $newQty = min($realAvailableQty, $totalQty);
                $sources[$i]['qtyToDeduct'] = $newQty;
                $diff = $newQty - $sources[$i]['qtyToDeduct'];
                $missedQty = $missedQty - $diff;
                if ($missedQty <= 0) {
                    break;
                }
            }

            //If real available qty is less than or equal to 0
            //then we will increase the missed qty to the qty to deduct
            //and set the qty to deduct to 0
            if ($realAvailableQty <= 0) {
                $missedQty += $sources[$i]['qtyToDeduct'];
                $sources[$i]['qtyToDeduct'] = 0;
                $substractQty = abs($realAvailableQty);
            }
            //If real available qty is less than qty to deduct
            //then we will increase the missed qty to the difference between qty to deduct and real available qty
            //and set the qty to deduct to the real available qty
            else if($realAvailableQty < $sources[$i]['qtyToDeduct']) {
                $sources[$i]['qtyToDeduct'] = $realAvailableQty;
                $missedQty += ($sources[$i]['qtyToDeduct'] - $realAvailableQty);
            }
        }

        return $sources;
    }
}
