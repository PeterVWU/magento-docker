<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Model\Email;

class PaymentRequestSender extends \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
{
    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @param \Magento\Sales\Model\Order\Email\Container\Template $templateContainer
     * @param Container\PaymentRequestIdentity $identityContainer
     * @param \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice $invoiceResource
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     * @param \Vapewholesaleusa\PaymentRequest\Model\TokenProvider $tokenProvider
     * @param \Magento\Framework\Url $urlBuilder
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Store\Model\App\Emulation|null $appEmulation
     */
    public function __construct(
        \Magento\Sales\Model\Order\Email\Container\Template                           $templateContainer,
        \Vapewholesaleusa\PaymentRequest\Model\Email\Container\PaymentRequestIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory                         $senderBuilderFactory,
        \Psr\Log\LoggerInterface                                                      $logger,
        \Magento\Sales\Model\Order\Address\Renderer                                   $addressRenderer,
        \Magento\Payment\Helper\Data                                                  $paymentHelper,
        \Magento\Sales\Model\ResourceModel\Order\Invoice                              $invoiceResource,
        \Magento\Framework\App\Config\ScopeConfigInterface                            $globalConfig,
        \Magento\Framework\Event\ManagerInterface                                     $eventManager,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config                $config,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\TokenProvider         $tokenProvider,
        private readonly \Magento\Framework\Url                                       $urlBuilder,
        private readonly \Magento\Framework\App\DeploymentConfig                      $deploymentConfig,
        \Magento\Store\Model\App\Emulation                                            $appEmulation = null
    ) {
        $this->appEmulation = $appEmulation ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Store\Model\App\Emulation::class);
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $invoiceResource,
            $globalConfig,
            $eventManager,
            $appEmulation
        );
    }

    /**
     * Sends Payment Request email to the customer.
     *
     * Email will be sent immediately in two cases:
     *
     * - if asynchronous email sending is disabled in global settings
     * - if $forceSyncMode parameter is set to TRUE
     *
     * Otherwise, email will be sent later during running of
     * corresponding cron job.
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param bool $forceSyncMode
     * @return bool
     * @throws \Exception
     */
    public function send(\Magento\Sales\Model\Order\Invoice $invoice, $forceSyncMode = false): bool
    {
        $this->identityContainer->setStore($invoice->getStore());
        $invoice->setSendEmail($this->identityContainer->isEnabled());

        $order = $invoice->getOrder();
        if ($this->checkIfPartialInvoice($order, $invoice)) {
            $order->setBaseSubtotal((float) $invoice->getBaseSubtotal());
            $order->setBaseTaxAmount((float) $invoice->getBaseTaxAmount());
            $order->setBaseShippingAmount((float) $invoice->getBaseShippingAmount());
        }
        $paymentHTML = $this->getPaymentHtml($order);
        $this->appEmulation->startEnvironmentEmulation(
            $order->getStoreId(),
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );
        $transport = [
            'order' => $order,
            'order_id' => $order->getId(),
            'invoice' => $invoice,
            'invoice_id' => $invoice->getId(),
            'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
            'billing' => $order->getBillingAddress(),
            'payment_html' => $paymentHTML,
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'is_not_virtual' => $order->getIsNotVirtual(),
                'email_customer_note' => $order->getEmailCustomerNote(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ],
            'token' => $this->tokenProvider->getToken(),
            'hosted_gateway_url' => $this->config->getHostedGatewayUrl(),
            'payment_request_url' => $this->getUrl((int)$order->getId(), (int)$invoice->getId())
        ];
        $transportObject = new \Magento\Framework\DataObject($transport);
        $this->appEmulation->stopEnvironmentEmulation();

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_invoice_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        if ($this->checkAndSend($order)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the order contains partial invoice
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return bool
     */
    private function checkIfPartialInvoice(
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Invoice $invoice
    ): bool {
        $totalQtyOrdered = (float) $order->getTotalQtyOrdered();
        $totalQtyInvoiced = (float) $invoice->getTotalQty();
        return $totalQtyOrdered !== $totalQtyInvoiced;
    }

    /**
     * Generates the secure payment request URL.
     *
     * @param int $orderId
     * @param int $invoiceId
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    private function getUrl(int $orderId, int $invoiceId): string
    {
        $secretKey = $this->deploymentConfig->get('crypt/key');
        $token = hash_hmac('sha256', $orderId . $invoiceId, $secretKey);

        $queryParams = [
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'token' => $token
        ];

        return $this->urlBuilder->getUrl(
            'vusa_payment_request_form/payment/form',
            ['_secure' => true,
                '_query' => $queryParams
            ]
        );
    }
}
