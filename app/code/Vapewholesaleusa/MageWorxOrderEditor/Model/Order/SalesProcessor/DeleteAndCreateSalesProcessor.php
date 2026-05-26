<?php

declare(strict_types=1);

namespace Vapewholesaleusa\MageWorxOrderEditor\Model\Order\SalesProcessor;

class DeleteAndCreateSalesProcessor extends \MageWorx\OrderEditor\Model\Order\SalesProcessor\DeleteAndCreateSalesProcessor
{
    /**
     * @var bool
     */
    protected $isInvoicePending = false;

    /**
     * @var \Vapewholesaleusa\PaymentRequest\Model\Config
     */
    protected $config;

    /**
     * @var \Magento\Sales\Model\Order\StatusResolver
     */
    protected $statusResolver;

    /**
     * @param \MageWorx\OrderEditor\Helper\Data $helperData
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \MageWorx\OrderEditor\Api\OrderRepositoryInterface $orderRepository
     * @param \MageWorx\OrderEditor\Api\OrderItemRepositoryInterface $oeOrderItemRepository
     * @param \Magento\Sales\Api\OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \MageWorx\OrderEditor\Api\ShipmentManagerInterface $shipmentManager
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     * @param \Magento\Sales\Model\Order\StatusResolver $statusResolver
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
        \Vapewholesaleusa\PaymentRequest\Model\Config $config,
        \Magento\Sales\Model\Order\StatusResolver     $statusResolver
    ) {
        parent::__construct(
            $helperData,
            $transactionFactory,
            $request,
            $orderRepository,
            $oeOrderItemRepository,
            $orderPaymentRepository,
            $shipmentManager,
            $eventManager
        );
        $this->config = $config;
        $this->statusResolver = $statusResolver;
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function updateInvoices(): void
    {
        if ($this->getOrder()->hasInvoices()) {
            if ($this->isOrderTotalIncreased()
                && $this->helperData->getIsAllowKeepPrevInvoice()  && $this->getOrder()->getPayment()->getMethod() !== 'checkmo'
            ) {
                // Updating invoice in +
                $this->createInvoiceForOrder();
            } else {
                // Create credit memo and update invoice
                $this->removeAllInvoices();
                $this->createInvoiceForOrder();
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function removeAllInvoices(): void
    {
        $invoices = $this->getOrder()->getInvoiceCollection();
        foreach ($invoices as $invoice) {
            $shippingMethodDataInRequest = $this->request->getPost('shipping_method');
            if ($invoice->getState() == '1') {
                $this->isInvoicePending = true;
            }
            if (!$invoice->isCanceled() && !empty($shippingMethodDataInRequest)) {
                $invoice->cancel();
                $transaction = $this->transactionFactory->create();
                $transaction->addObject($invoice->getOrder())->save();
            }
            $invoice->delete();
        }

        $invoices->removeAllItems();

        foreach ($this->getOrder()->getAllItems() as $orderItem) {
            $orderItem->setQtyInvoiced(0)
                ->setRowInvoiced(0)
                ->setBaseRowInvoiced(0)
                ->setTaxInvoiced(0)
                ->setBaseTaxInvoiced(0)
                ->setDiscountInvoiced(0)
                ->setBaseDiscountInvoiced(0)
                ->setGwPriceInvoiced(0)
                ->setGwBasePriceInvoiced(0)
                ->setGwTaxAmountInvoiced(0)
                ->setGwBaseTaxAmountInvoiced(0)
                ->setDiscountTaxCompensationInvoiced(0)
                ->setBaseDiscountTaxCompensationInvoiced(0);

            $this->oeOrderItemRepository->save($orderItem);
        }

        $this->getOrder()
            ->setTaxInvoiced(0)->setBaseTaxInvoiced(0)
            ->setDiscountInvoiced(0)->setBaseDiscountInvoiced(0)
            ->setSubtotalInvoiced(0)->setBaseSubtotalInvoiced(0)
            ->setTotalInvoiced(0)->setBaseTotalInvoiced(0)
            ->setShippingInvoiced(0)->setBaseShippingInvoiced(0)
            ->setTotalPaid(0)->setBaseTotalPaid(0);
        if ($this->isInvoicePending) {
            $this->getOrder()->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        } else {
            $this->getOrder()->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        }
        $this->orderRepository->save($this->getOrder());
    }

    /**
     * @return void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function createInvoiceForOrder(): void
    {
        if (($this->getOrder()->getPayment()->getMethod() === 'mageworx_ordereditor_payment_method' ||
                $this->getOrder()->getPayment()->getMethod() === 'checkmo') &&
            $this->config->isEnabled() == '1') {
            $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $status = $this->statusResolver->getOrderStatusByState($this->getOrder(), $state);
            $this->getOrder()->setState($state);
            $this->getOrder()->setStatus($status);
        } else {
            $this->getOrder()
                ->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $this->orderRepository->save($this->getOrder());
        }
        if ($this->getOrder()->canInvoice()) {
            $order   = $this->getOrder();
            $invoice = $this->getOrder()->prepareInvoice();
            if (!$invoice) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Can not create invoice'));
            }
            if ($this->isInvoicePending ||
                ($this->getOrder()->getPayment()->getMethod() === 'mageworx_ordereditor_payment_method' ||
                    $this->getOrder()->getPayment()->getMethod() === 'checkmo')) {
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::NOT_CAPTURE);
            } else {
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            }
            $invoice->register();

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

            /* hack for fix lost $0.01 */
            $payment = $order->getPayment();
            $payment->setBaseAmountPaid($order->getBaseGrandTotal())
                ->setAmountPaid($order->getGrandTotal());

            $this->orderPaymentRepository->save($payment);

            $order->setBaseTotalPaid($order->getBaseGrandTotal())
                ->setTotalPaid($order->getGrandTotal());

            $this->orderRepository->save($order);
        }
    }

}
