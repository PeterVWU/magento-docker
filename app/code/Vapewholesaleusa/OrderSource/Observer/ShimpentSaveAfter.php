<?php

namespace Vapewholesaleusa\OrderSource\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Services\AddShipmentService;

/**
 * Class ShimpentSaveAfter
 */
class ShimpentSaveAfter implements ObserverInterface
{
    /**
     * @var AddShipmentService
     */
    private $addShipmentService;

    /**
     * @var Data
     */
    private $orderSourceHelper;

    /**
     * @param AddShipmentService $addShipmentService
     * @param Data $orderSourceHelper
     */
    public function __construct(
        AddShipmentService $addShipmentService,
        Data $orderSourceHelper
    ) {
        $this->addShipmentService = $addShipmentService;
        $this->orderSourceHelper = $orderSourceHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->orderSourceHelper->isModuleEnabled()){
            return;
        }

        /** @var ShipmentItemInterface $shipment */
        $shipment = $observer->getEvent()->getShipment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();
        /** @var ShipmentItemInterface[] $items */
        $items = $shipment->getAllItems();
        foreach ($items as $item) {
            if($item->getQty() == 0){
                continue;
            }

            $this->addShipmentService->execute(
                $order->getId(),
                $item->getSku(),
                $shipment->getExtensionAttributes()->getSourceCode(),
                $item->getQty()
            );
        }
    }
}
