<?php

namespace Vapewholesaleusa\OrderSource\Engines;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Vapewholesaleusa\OrderSource\Api\ArrangeSourceInterface;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterfaceFactory;
use Vapewholesaleusa\OrderSource\Api\OrderSourceRepositoryInterface;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;

/**
 * Class OrderSourceEngine
 */
class OrderSourceEngine
{
    /**
     * @var OrderSourceRepositoryInterface
     */
    private $orderSourceRepository;

    /**
     * @var OrderSourceInterfaceFactory
     */
    private $orderSourceFactory;

    /**
     * @var ArrangeSourceCollectorEngine
     */
    private $arrangeSourceCollectorEngine;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Stock constructor.
     *
     * @param OrderSourceRepositoryInterface $orderSourceRepository
     * @param OrderSourceInterfaceFactory $orderSourceFactory
     * @param ArrangeSourceCollectorEngine $arrangeSourceCollectorEngine
     * @param Data $helper
     */
    public function __construct(
        OrderSourceRepositoryInterface $orderSourceRepository,
        OrderSourceInterfaceFactory    $orderSourceFactory,
        ArrangeSourceCollectorEngine    $arrangeSourceCollectorEngine,
        Data                            $helper
    ) {
        $this->orderSourceRepository = $orderSourceRepository;
        $this->orderSourceFactory = $orderSourceFactory;
        $this->arrangeSourceCollectorEngine = $arrangeSourceCollectorEngine;
        $this->helper = $helper;
    }

    /**
     * @param OrderInterface $order
     * @param OrderItemInterface $item
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(OrderInterface $order, OrderItemInterface $item)
    {
        $sku = $item->getSku();
        $qty = $item->getQtyOrdered();
        $itemId = $item->getQuoteItemId();
        $orderIncrementId = $order->getIncrementId();
        $orderId = $order->getId();
        $arragmentAlgorithm = $this->getArragmentClass();
        if(!$arragmentAlgorithm) return null;

        $sources = $arragmentAlgorithm->execute($orderId, $sku, $qty);

        foreach ($sources as $source) {
            $sourceCode = $source['sourceCode'];
            $qty = $source['qtyToDeduct'];

            if ($qty <= 0) {
                continue;
            }

            /** @var OrderSourceInterface $orderSource */
            $orderSource = $this->orderSourceFactory->create(
                [
                    'data' => [
                        'order_id' => (int)$orderId,
                        'order_inc_id' => $orderIncrementId,
                        'item_id' => (int)$itemId,
                        'sku' => $sku,
                        'source_code' => $sourceCode,
                        'qty' => (float)$qty
                    ]
                ]
            );
            $this->orderSourceRepository->save($orderSource);
        }

        return $sources;
    }

    /**
     * @return ArrangeSourceInterface|null
     */
    private function getArragmentClass(): ?ArrangeSourceInterface
    {
        $code = $this->helper->getAssigmentAlgorithm() ?? null;
        $class = $this->arrangeSourceCollectorEngine->getArrangeSourcesByCode($code);
        return $class;
    }
}
