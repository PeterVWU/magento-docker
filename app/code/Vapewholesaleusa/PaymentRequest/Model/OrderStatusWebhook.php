<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Model;

class OrderStatusWebhook implements \Vapewholesaleusa\PaymentRequest\Api\OrderStatusWebhookInterface
{
    /**
     * @param \Vapewholesaleusa\PaymentRequest\Service\Payment $paymentService
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory
     */
    public function __construct(
        private readonly \Vapewholesaleusa\PaymentRequest\Service\Payment $paymentService,
        private readonly \Psr\Log\LoggerInterface                         $logger,
        private readonly \Magento\Framework\Webapi\Rest\Request           $request,
        private readonly \Magento\Framework\DB\Transaction                $transaction,
        private readonly \Magento\Sales\Model\Order\InvoiceFactory        $invoiceFactory
    ) {
    }

    /**
     *  Updates the order status based on the webhook or API data.
     *
     * @return void
     * @throws \Exception
     */
    public function execute(): void
    {
        // Get raw JSON from the request body
        $webhookData = $this->request->getContent();

        // Decode JSON into an array
        $decodedData = json_decode($webhookData, true);

        // Check if the data was decoded correctly
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON received.');
        }
        if (isset($decodedData['payload']['id'])) {
            try {
                $transactionDetails = $this->paymentService->getTransactionDetails($decodedData['payload']['id']);

                if (isset($transactionDetails['transactionStatus'])) {
                    if (isset($transactionDetails['order']['description']) &&
                        $transactionDetails['order']['description'] == 'Payment Request') {

                        if (!isset($transactionDetails['order']['invoiceNumber']) ||
                            empty($transactionDetails['order']['invoiceNumber'])) {
                            throw new \Magento\Framework\Exception\LocalizedException(__(
                                'Invoice number is missing in transaction details.'
                            ));
                        }

                        $invoice = $this->invoiceFactory->create()
                            ->loadByIncrementId($transactionDetails['order']['invoiceNumber']);

                        if (!$invoice->getId()) {
                            throw new \Magento\Framework\Exception\LocalizedException(__('Invoice not found.'));
                        }

                        $invoice->capture();
                        $order = $invoice->getOrder();
                        $order->addCommentToStatusHistory(__(
                            'Authorize Transaction ID: %1',
                            $decodedData['payload']['id']
                        ));
                        $invoice->addComment(__(
                            'Authorize Transaction ID: %1',
                            $decodedData['payload']['id']
                        ));

                        $this->transaction->addObject($invoice)->addObject($order)->save();
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
    }
}
