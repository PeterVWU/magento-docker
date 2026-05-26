<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Plugin\Button;

class ToolbarPlugin
{
    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     * @param \Vapewholesaleusa\PaymentRequest\Ui\Component\Button\DataProvider $dataProvider
     */
    public function __construct(
        private readonly \Magento\Framework\AuthorizationInterface                         $authorization,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config                     $config,
        private readonly \Vapewholesaleusa\PaymentRequest\Ui\Component\Button\DataProvider $dataProvider
    ) {
    }

    /**
     * Add Payment Request button.
     *
     * @param \Magento\Backend\Block\Widget\Button\ToolbarInterface $subject
     * @param \Magento\Framework\View\Element\AbstractBlock $context
     * @param \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\ToolbarInterface $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ): void {
        $nameInLayout = $context->getNameInLayout();

        $salesInstances = $this->getSalesInstances($nameInLayout, $context);
        if ($salesInstances === null) {
            return;
        }
        $order = $salesInstances['order'];
        $invoice = $salesInstances['invoice'];
        /** @var \Magento\Sales\Model\Order $order */
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        if ($order
            && $invoice
            && !empty($order['customer_id'])
            && $this->config->isEnabled()
            && $this->authorization->isAllowed('Vapewholesaleusa_PaymentRequest::payment_request')
            && $order->getPayment()->getMethod() == 'mageworx_ordereditor_payment_method'
            && $invoice->getState() == \Magento\Sales\Model\Order\Invoice::STATE_OPEN
        ) {
            $orderId = (int)$order->getId();
            $invoiceId = (int)$invoice->getId();
            $buttonList->add(
                'payment_request',
                $this->dataProvider->getData($orderId, $invoiceId),
                -1
            );
        }
    }

    /**
     * Extract order data from context.
     *
     * @param string $nameInLayout
     * @param \Magento\Framework\View\Element\AbstractBlock $context
     * @return array|null
     */
    private function getSalesInstances(
        string $nameInLayout,
        \Magento\Framework\View\Element\AbstractBlock $context
    ): array|null {
        return match ($nameInLayout) {
            'sales_invoice_view' => [
                'order' => $context->getInvoice()->getOrder(),
                'invoice' => $context->getInvoice()
            ],
            default => null,
        };
    }
}
