<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Ui\Component\Button;

class DataProvider
{
    /**
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        private readonly \Magento\Framework\Escaper      $escaper,
        private readonly \Magento\Framework\UrlInterface $urlBuilder,
        private readonly array                           $data = []
    ) {
    }

    /**
     * Get data for Payment Request button.
     *
     * @param int $orderId
     * @param int $invoiceId
     * @return array
     */
    public function getData(int $orderId, int $invoiceId): array
    {
        $buttonData = [
            'on_click' => 'window.paymentRequestConfirmationPopup("'
                . $this->escaper->escapeHtml(
                    $this->escaper->escapeJs($this->getPaymentRequestUrl($orderId, $invoiceId))
                )
                . '")',
        ];

        return array_merge_recursive($buttonData, $this->data);
    }

    /**
     * Get Payment Request url.
     *
     * @param int $orderId
     * @param int $invoiceId
     * @return string
     */
    private function getPaymentRequestUrl(int $orderId, int $invoiceId): string
    {
        return $this->urlBuilder->getUrl(
            'vusa_payment_request/order/sendpaymentrequest',
            ['order_id' => $orderId, 'invoice_id' => $invoiceId]
        );
    }
}
