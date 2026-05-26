<?php

namespace Vapewholesaleusa\MageWorxOrderEditor\Model\Order\SalesProcessor;

class KeepUntouchedSalesProcessor extends \MageWorx\OrderEditor\Model\Order\SalesProcessor\KeepUntouchedSalesProcessor
{
    /**
     * @param \MageWorx\OrderEditor\Helper\Data $helperData
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \MageWorx\OrderEditor\Api\OrderRepositoryInterface $orderRepository
     * @param \MageWorx\OrderEditor\Api\OrderItemRepositoryInterface $oeOrderItemRepository
     * @param \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \MageWorx\OrderEditor\Api\ShipmentManagerInterface $shipmentManager
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \MageWorx\OrderEditor\Model\Order\SalesProcessor\KeepUntouchedSalesProcessor\Creditmemo\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\RefundInvoiceInterface $refundInvoice
     * @param \Magento\Sales\Api\Data\CreditmemoCommentCreationInterfaceFactory $creditmemoCommentCreationFactory
     * @param \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoService
     * @param \Magento\Framework\Registry $registry
     * @param \MageWorx\OrderEditor\Api\InvoiceFinderInterface $invoiceFinder
     */
    public function __construct(
        \MageWorx\OrderEditor\Helper\Data $helperData,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\App\Request\Http $request,
        \MageWorx\OrderEditor\Api\OrderRepositoryInterface $orderRepository,
        \MageWorx\OrderEditor\Api\OrderItemRepositoryInterface $oeOrderItemRepository,
        \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository,
        \MageWorx\OrderEditor\Api\ShipmentManagerInterface $shipmentManager,
        \Magento\Framework\Event\Manager $eventManager,
        \MageWorx\OrderEditor\Model\Order\SalesProcessor\KeepUntouchedSalesProcessor\Creditmemo\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\RefundInvoiceInterface $refundInvoice,
        \Magento\Sales\Api\Data\CreditmemoCommentCreationInterfaceFactory $creditmemoCommentCreationFactory,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoService,
        \Magento\Framework\Registry $registry,
        \MageWorx\OrderEditor\Api\InvoiceFinderInterface $invoiceFinder
    ) {
        parent::__construct(
            $helperData,
            $transactionFactory,
            $request,
            $orderRepository,
            $oeOrderItemRepository,
            $orderPaymentRepository,
            $shipmentManager,
            $eventManager,
            $creditmemoFactory,
            $creditmemoRepository,
            $refundInvoice,
            $creditmemoCommentCreationFactory,
            $creditmemoService,
            $registry,
            $invoiceFinder
        );
    }

    /**
     * @return void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createInvoiceForOrder(\Magento\Sales\Api\Data\OrderInterface $order = null): void
    {
        $order = $order ?? $this->getOrder();
        if (!$order instanceof \MageWorx\OrderEditor\Model\Order) {
            throw new \MageWorx\OrderEditor\Exception\CriticalWorkflowException(
                __('OrderEditor model of the order expected, %s provided', get_class($order))
            );
        }

        if (!$this->isNeedToCreateInvoiceForTheOrder($order)) {
            return;
        }

        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $this->orderRepository->save($order);

        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            if (empty($invoice)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Can not create invoice'));
            }

            /**
             * We must add shipping amount manually, because magento totals collector drops any calculation in case
             * when at least one previous invoice is not canceled and has shipping amount invoiced.
             *
             * @see \Magento\Sales\Model\Order\Invoice\Total\Shipping::collect() lines 31-33 .
             */
            $changesInShipping = $order->getChangesInShipping();
            if (!empty($changesInShipping['base_amount']) && $changesInShipping['base_amount'] > 0) {
                $baseShippingAmount = $changesInShipping['base_amount'];
                $shippingAmount     = $order->getBaseToOrderRate() * $baseShippingAmount;

                $invoice->setShippingAmount($shippingAmount);
                $invoice->setBaseShippingAmount($baseShippingAmount);
                $invoice->setShippingInclTax($order->getBaseToOrderRate() * $changesInShipping['base_amount_incl_tax']);
                $invoice->setBaseShippingInclTax($changesInShipping['base_amount_incl_tax']);

                $invoice->setGrandTotal($invoice->getGrandTotal() + $shippingAmount);
                $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseShippingAmount);
            }

            // Do not create an empty invoice
            if ($this->isEmptyInvoice($invoice)) {
                return;
            }

            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::NOT_CAPTURE);
            $invoice->register();

            // Update amount invoiced in each changed item to prevent errors with subsequent refunds
            $this->processOrderItemsInvoicedAmounts($order);

            $databaseTransaction = $this->transactionFactory->create();
            $databaseTransaction->addObject($invoice)->addObject($invoice->getOrder())->save();

            $this->eventManager->dispatch(
                'mageworx_log_changes_on_order_edit',
                [
                    \MageWorx\OrderEditor\Api\ChangeLoggerInterface::SIMPLE_MESSAGE_KEY => __(
                        'New Invoice <b>%1</b> has been created <b>(%2)</b>',
                        $invoice->getIncrementId(),
                        $invoice->getOrder()->formatPriceTxt($invoice->getGrandTotal())
                    )
                ]
            );

            $this->orderRepository->save($order);
        }
    }

    /**
     * Check each item in the order which has been changed and update corresponding totals (invoiced amounts).
     * It is necessary to prevent errors in subsequent creditmemos.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     */
    private function processOrderItemsInvoicedAmounts(\Magento\Sales\Api\Data\OrderInterface $order): void
    {
        $changesInAmount = $order->getChangesInAmounts();
        if (!empty($changesInAmount)) {
            // Changes will be saved with the order save
            foreach ($changesInAmount as $itemId => $itemChanges) {
                $orderItem = $order->getItemById($itemId);

                $invoicedBaseAmount    = (float)$orderItem->getBaseRowInvoiced(
                ); // Get total invoiced from the beginning of the order
                $newBaseAmount         = (float)$orderItem->getBaseRowTotalInclTax(); // Get actual row total for now
                $baseAmountRefunded    = (float)$orderItem->getBaseAmountRefunded(
                ); // Get actual row total refunded for now
                $baseRealTotalInvoiced = $invoicedBaseAmount - $baseAmountRefunded; // Calculate real invoiced total (invoiced minus refunded for now) for now

                if ($baseRealTotalInvoiced < $newBaseAmount) { // We must create new creditmemo only when total real invoiced amount is smaller than new base amount
                    $orderItem->setBaseRowInvoiced((float)$orderItem->getBaseRowTotal())
                        ->setRowInvoiced((float)$orderItem->getRowTotal())
                        ->setBaseDiscountInvoiced($orderItem->getBaseDiscountAmount())
                        ->setDiscountInvoiced($orderItem->getDiscountAmount())
                        ->setBaseTaxInvoiced($orderItem->getBaseTaxAmount())
                        ->setTaxInvoiced($orderItem->getTaxAmount())
                        ->setGwBasePriceInvoiced($orderItem->getGwBasePrice())
                        ->setGwPriceInvoiced($orderItem->getGwPrice())
                        ->setGwBaseTaxAmountInvoiced($orderItem->getGwBaseTaxAmount())
                        ->setGwTaxAmountInvoiced($orderItem->getGwTaxAmount());
                }
            }
        }
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Api\Data\OrderItemInterface[] $orderItems
     */
    private function cancelOrderItems(\Magento\Sales\Api\Data\OrderInterface $order, array $orderItems = []): void
    {// if not empty $orderItems
        foreach ($orderItems as $orderItemId => $orderItem) {
            $qtyToCancel = $orderItem->getQtyOrdered() - $orderItem->getQtyCanceled() - $orderItem->getQtyRefunded();
            $orderItem->setQtyCanceled($orderItem->getQtyCanceled() + $qtyToCancel);
        }

        $databaseTransaction = $this->transactionFactory->create();
        $databaseTransaction->addObject($order)->save();
    }

    /**
     * When order has a difference in totals and real refund is not necessary we must correct item totals
     * manually to prevent future errors in calculations.
     *
     * @param \MageWorx\OrderEditor\Model\Order $order
     * @return void
     */
    private function correctItemTotalsWhenRealRefundIsNotNecessary(\MageWorx\OrderEditor\Model\Order $order): void
    {
        $changesInAmount = $order->getChangesInAmounts();
        if (!empty($changesInAmount)) {
            foreach ($changesInAmount as $orderItemId => $data) {
                $rowTotalChanges = $data['base_row_total'];
                if ($rowTotalChanges < 0) {
                    $orderItem = $order->getItemById($orderItemId);
                    if ($orderItem->getQtyInvoiced() > 0) {
                        $newBaseAmountRefunded = $orderItem->getBaseAmountRefunded() + abs($rowTotalChanges);
                        $newAmountRefunded     = $order->getBaseToOrderRate() * $newBaseAmountRefunded;
                        $orderItem->setBaseAmountRefunded($newBaseAmountRefunded);
                        $orderItem->setAmountRefunded($newAmountRefunded);
                    }
                }
            }
        }
    }

    /**
     * Refund amount without physical item return
     * Used when item price decreased
     *
     * @param float $amount
     * @param \MageWorx\OrderEditor\Model\Order $order
     * @param array $refundBaseAmountPerInvoice
     * @throws \Exception
     */
    private function refundBaseAmountWithoutItem(
        float $amount,
        \MageWorx\OrderEditor\Model\Order $order,
        array $refundBaseAmountPerInvoice = [],
        float $invoiceBaseAmountCompensation = 0
    ): void {
        $isOnline = (bool)$order->getPayment()->getLastTransId();

        $creditmemoData                                         = [];
        $creditmemoData['do_offline']                           = !$isOnline;
        $creditmemoData['comment_text']                         = 'Automatically created creditmemo';
        $creditmemoData['shipping_amount']                      = 0;
        $creditmemoData['adjustment_positive']                  = 0;
        $creditmemoData['adjustment_negative']                  = 0;
        $creditmemoData['refund_customerbalance_return_enable'] = 0; // Refund to customer balance
        $creditmemoData['refund_without_items']                 = true; // Flag for factory to prevent items calculations

        $creditMemosData = [];
        foreach ($refundBaseAmountPerInvoice as $invoiceId => $data) {
            $baseAmountToReturn = $data['base_amount_to_return'];

            if ($invoiceBaseAmountCompensation >= $baseAmountToReturn) {
                $invoiceBaseAmountCompensation -= $baseAmountToReturn;
                continue;
            } else {
                $baseAmountToReturn -= $invoiceBaseAmountCompensation;
            }

            $creditMemosData[$invoiceId]                        = $creditmemoData;
            $creditMemosData[$invoiceId]['invoice']             = $data['invoice'];
            $creditMemosData[$invoiceId]['adjustment_positive'] = $baseAmountToReturn;
        }

        $this->createCreditMemoAndRefund($order, $creditMemosData);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface|\Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Api\Data\OrderItemInterface[] $orderItems
     * @return void
     * @throws \Exception
     */
    private function createCreditmemoForOrderItems(
        \Magento\Sales\Api\Data\OrderInterface $order,
        array                                  $orderItems = [],
        array                                  $data = []
    ): void {
        $order                 = $this->getOrder();
        $decreasedItems = $order->getDecreasedItems();
        $removedItems   = $order->getRemovedItems(); // @TODO Removed items must has full qty
        // Removed items has priority against decreased items. Do not change the order!
        $itemsToRemove  = $removedItems + $decreasedItems;

        // Prepare creditmemo data
        $creditMemosData = [];

        // Prepare items qty
        foreach ($orderItems as $orderItem) {
            $orderItemId = (int)$orderItem->getItemId();
            $qtyToRefund = !empty($itemsToRemove[$orderItemId])
                ? (float)$itemsToRemove[$orderItemId] // Item has decreased qty and not fully removed from the order
                : (float)$orderItem->getQtyInvoiced() - (float)$orderItem->getQtyRefunded(); // Item totally removed from the order

            $invoices = $this->invoiceFinder->getInvoiceByOrderItemId(
                $orderItemId,
                (int)$order->getId(),
                $qtyToRefund
            );

            foreach ($invoices as $invoice) {
                /**
                 * Getting the item from invoice corresponding to the item from order.
                 * We need to detect actual invoice for creditmemo for online refunds (transactions).
                 */
                $invoiceItems           = $invoice->getItems();
                $invoiceItemByOrderItem = null;
                foreach ($invoiceItems as $invoiceItem) {
                    if ((int)$invoiceItem->getOrderItemId() === $orderItemId) {
                        $invoiceItemByOrderItem = $invoiceItem;
                        break;
                    }
                }

                if ($invoiceItemByOrderItem === null) {
                    throw new \Magento\Framework\Exception\NoSuchEntityException(
                        __('Unable to locate invoice for order item with id %1', $orderItemId)
                    );
                }

                /**
                 * Getting minimum qty to refund according current invoice item. Finally, the $qtyToRefund must be 0,
                 * but it may need more than one invoice item (more than one invoice) in case when item
                 * has been invoiced twice or more time.
                 */
                $qty         = min($qtyToRefund, $invoiceItemByOrderItem->getQty());
                $qtyToRefund -= $qty; // During next iteration we must see reduced qty to refund to process correct qty.

                if (empty($creditMemosData[$invoice->getEntityId()])) {
                    $creditMemosData[$invoice->getEntityId()] = $data;
                }

                $creditMemosData[$invoice->getEntityId()]['invoice']                    = $invoice;
                $creditMemosData[$invoice->getEntityId()]['qtys'][$orderItemId]         = $qty;
                $creditMemosData[$invoice->getEntityId()]['items'][$orderItemId]['qty'] = $qty;
            }
        }

        $this->createCreditMemoAndRefund($order, $creditMemosData);
    }
}
