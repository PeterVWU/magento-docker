<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Controller\Adminhtml\Order\Invoice;

class Save extends \Magento\Sales\Controller\Adminhtml\Order\Invoice\Save
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::sales_invoice';
    /**
     * @var \Magento\Sales\Helper\Data
     */
    private $salesData;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender
     * @param \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Vapewholesaleusa\PaymentRequest\Service\Payment $paymentService
     * @param \Vapewholesaleusa\PaymentRequest\Model\Email\PaymentRequestSender $paymentRequestSender
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     * @param \Vapewholesaleusa\PaymentRequest\Model\TokenProvider $tokenProvider
     * @param \Magento\Sales\Helper\Data|null $salesData
     */
    public function __construct(
        \Magento\Backend\App\Action\Context                                                $context,
        \Magento\Framework\Registry                                                        $registry,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender                              $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender                             $shipmentSender,
        \Magento\Sales\Model\Order\ShipmentFactory                                         $shipmentFactory,
        private readonly \Magento\Sales\Model\Service\InvoiceService                       $invoiceService,
        private readonly \Vapewholesaleusa\PaymentRequest\Service\Payment                  $paymentService,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Email\PaymentRequestSender $paymentRequestSender,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config                     $config,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\TokenProvider              $tokenProvider,
        \Magento\Sales\Helper\Data                                                         $salesData = null
    ) {
        $this->salesData = $salesData ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Sales\Helper\Data::class);
        parent::__construct(
            $context,
            $registry,
            $invoiceSender,
            $shipmentSender,
            $shipmentFactory,
            $invoiceService,
            $salesData
        );
    }

    /**
     * Save invoice
     *
     * We can save only new invoice. Existing invoices are not editable
     *
     * @return \Magento\Framework\Controller\ResultInterface
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @codeCoverageIgnore
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isPost = $this->getRequest()->isPost();
        if (!$formKeyIsValid || !$isPost) {
            $this->messageManager
                ->addErrorMessage(__("The invoice can't be saved at this time. Please try again later."));
            return $resultRedirect->setPath('sales/order/index');
        }

        $data = $this->getRequest()->getPost('invoice');
        $orderId = $this->getRequest()->getParam('order_id');

        if (!empty($data['comment_text'])) {
            $this->_objectManager->get(\Magento\Backend\Model\Session::class)->setCommentText($data['comment_text']);
        }

        try {
            $invoiceData = $this->getRequest()->getParam('invoice', []);
            $invoiceItems = isset($invoiceData['items']) ? $invoiceData['items'] : [];
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class)->load($orderId);
            if (!$order->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The order no longer exists.'));
            }

            if (!$order->canInvoice()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The order does not allow an invoice to be created.')
                );
            }

            $invoice = $this->invoiceService->prepareInvoice($order, $invoiceItems);

            if (!$invoice->getTotalQty()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("The invoice can't be created without products. Add products and try again.")
                );
            }
            $this->registry->register('current_invoice', $invoice);
            if (!empty($data['capture_case'])) {
                $invoice->setRequestedCaptureCase($data['capture_case']);
            }

            if (!empty($data['comment_text'])) {
                $invoice->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );

                $invoice->setCustomerNote($data['comment_text']);
                $invoice->setCustomerNoteNotify(isset($data['comment_customer_notify']));
            }
            $invoice->register();

            $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $invoice->getOrder()->setIsInProcess(true);

            $transactionSave = $this->_objectManager->create(
                \Magento\Framework\DB\Transaction::class
            )->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $shipment = false;
            if (!empty($data['do_shipment']) || (int)$invoice->getOrder()->getForcedShipmentWithInvoice()) {
                $shipment = $this->_prepareShipment($invoice);
                if ($shipment) {
                    $transactionSave->addObject($shipment);
                }
            }
            $transactionSave->save();

            // send invoice/shipment emails
            try {
                if (!empty($data['send_email']) && $this->salesData->canSendNewInvoiceEmail()) {
                    $this->invoiceSender->send($invoice);
                }
            } catch (\Exception $e) {
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            // send payment request emails
            try {
                if (!empty($data['payment_request_email']) &&
                    ($this->config->isEnabled() == '1')
                    && $order->getPayment()->getMethod() == 'mageworx_ordereditor_payment_method'
                ) {
                    $token = $this->paymentService->generateHostedPaymentPage($order, $invoice);
                    $this->tokenProvider->setToken($token);
                    $this->paymentRequestSender->send($invoice);
                }
            } catch (\Exception $e) {
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->messageManager->addErrorMessage(__('We can\'t send the payment request email right now.'));
            }

            if ($shipment) {
                try {
                    if (!empty($data['send_email']) && $this->salesData->canSendNewShipmentEmail()) {
                        $this->shipmentSender->send($shipment);
                    }
                } catch (\Exception $e) {
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                    $this->messageManager->addErrorMessage(__('We can\'t send the shipment right now.'));
                }
            }
            if (!empty($data['do_shipment'])) {
                $this->messageManager->addSuccessMessage(__('You created the invoice and shipment.'));
            } else {
                $this->messageManager->addSuccessMessage(__('The invoice has been created.'));
            }
            $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getCommentText(true);
            return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __("The invoice can't be saved at this time. Please try again later.")
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
        return $resultRedirect->setPath('sales/*/new', ['order_id' => $orderId]);
    }
}
