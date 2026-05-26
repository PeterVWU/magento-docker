<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Controller\Adminhtml\Order;

class SendPaymentRequest extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Vapewholesaleusa_PaymentRequest::payment_request';

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Vapewholesaleusa\PaymentRequest\Service\Payment $paymentService
     * @param \Vapewholesaleusa\PaymentRequest\Model\Email\PaymentRequestSender $paymentRequestSender
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Vapewholesaleusa\PaymentRequest\Model\TokenProvider $tokenProvider
     */
    public function __construct(
        \Magento\Backend\App\Action\Context                                                $context,
        private readonly \Magento\Framework\Controller\Result\JsonFactory                  $jsonFactory,
        private readonly \Vapewholesaleusa\PaymentRequest\Service\Payment                  $paymentService,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Email\PaymentRequestSender $paymentRequestSender,
        private readonly \Magento\Sales\Api\OrderRepositoryInterface                       $orderRepository,
        private readonly \Magento\Sales\Api\InvoiceRepositoryInterface                     $invoiceRepository,
        private readonly \Psr\Log\LoggerInterface                                          $logger,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\TokenProvider              $tokenProvider,
    ) {
        parent::__construct($context);
    }

    /**
     * Sends a hosted payment link to the customer.
     *
     * @return \Magento\Framework\Controller\Result\Json JSON response with success or failure.
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException If the order is not found.
     * @throws \Exception If `order_id` is missing or an error occurs.
     */

    public function execute(): mixed
    {
        $resultJson = $this->jsonFactory->create();
        try {
            $params = $this->getRequest()->getParams();
            if (!isset($params['order_id'])) {
                throw new \Exception('Order ID is missing.');
            }
            if (!isset($params['invoice_id'])) {
                throw new \Exception('Invoice ID is missing.');
            }
            $order = $this->orderRepository->get($params['order_id']);
            /** @var \Magento\Sales\Model\Order $order */
            $invoice = $this->invoiceRepository->get($params['invoice_id']);
            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $token = $this->paymentService->generateHostedPaymentPage($order, $invoice);

            $this->tokenProvider->setToken($token);

            if ($this->paymentRequestSender->send($invoice)) {
                $order->addCommentToStatusHistory(__('Payment request sent by Store Administrator'));
                $invoice->addComment('Payment request sent by Store Administrator');
                $this->orderRepository->save($order);
                $this->invoiceRepository->save($invoice);
            }
            return $resultJson->setData([
                'success'     => true,
                'message'     => __('Payment request sent successfully.')
            ]);
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            return $resultJson->setData([
                'success' => false,
                'message' => $exception->getMessage()
            ]);
        }
    }
}
