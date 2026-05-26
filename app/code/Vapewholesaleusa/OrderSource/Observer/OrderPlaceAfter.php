<?php
/**
 * Copyright © 2023 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Vapewholesaleusa\OrderSource\Engines\OrderSourceEngine;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Services\RemoveAllSourcesOfOrderService;

/**
 * Class OrderPlaceAfter
 */
class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var RemoveAllSourcesOfOrderService
     */
    private $removeAllSourcesOfOrderService;

    /**
     * @var OrderSourceEngine
     */
    private $orderSourceEngine;

    /**
     * @param RemoveAllSourcesOfOrderService $removeAllSourcesOfOrderService
     * @param Data $helperData
     * @param OrderSourceEngine $orderSourceEngine
     */
    public function __construct(
        RemoveAllSourcesOfOrderService $removeAllSourcesOfOrderService,
        Data                           $helperData,
        OrderSourceEngine              $orderSourceEngine
    ) {
        $this->removeAllSourcesOfOrderService = $removeAllSourcesOfOrderService;
        $this->helperData = $helperData;
        $this->orderSourceEngine = $orderSourceEngine;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (!$this->helperData->isModuleEnabled()) {
            return;
        }

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$observer->getEvent()->getDataObject()->getIsNewRecord()) {
            //Remove all asigned sources in case of order is canceled
            if ($order->getStatus() == Order::STATE_CANCELED) {
                $this->removeAllSourcesOfOrderService->execute((int)$order->getId());
            }

            return;
        }

        $orderItems = $order->getAllVisibleItems();
        foreach ($orderItems as $item) {
            /** @var OrderItemInterface $item */
            if ($item->getProductType() != 'simple') {
                continue;
            }
            $this->orderSourceEngine->execute($order, $item);
        }
    }
}
