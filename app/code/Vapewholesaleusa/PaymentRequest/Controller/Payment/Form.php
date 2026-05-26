<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Controller\Payment;

class Form implements \Magento\Framework\App\ActionInterface
{
    /**
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param \Vapewholesaleusa\PaymentRequest\Service\Payment $paymentService
     * @param \Vapewholesaleusa\PaymentRequest\Model\TokenProvider $tokenProvider
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private readonly \Magento\Framework\View\Result\PageFactory           $pageFactory,
        private readonly \Magento\Framework\App\RequestInterface              $request,
        private readonly \Magento\Sales\Api\OrderRepositoryInterface          $orderRepository,
        private readonly \Magento\Sales\Api\InvoiceRepositoryInterface        $invoiceRepository,
        private readonly \Vapewholesaleusa\PaymentRequest\Service\Payment     $paymentService,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\TokenProvider $tokenProvider,
        private readonly \Magento\Framework\App\DeploymentConfig              $deploymentConfig,
        private readonly \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        private readonly \Psr\Log\LoggerInterface                             $logger
    ) {
    }

    /**
     * Renders the payment form with the provided token.
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     *
     * @throws \InvalidArgumentException If the token is missing.
     * @throws \Exception If an error occurs during execution.
     */
    public function execute(): \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
    {
        try {
            $params = $this->request->getParams();
            if (!isset($params['order_id'])) {
                throw new \Exception('Order ID is missing.');
            }
            if (!isset($params['invoice_id'])) {
                throw new \Exception('Invoice ID is missing.');
            }
            if (!isset($params['token'])) {
                throw new \Exception('Token is missing.');
            }
            $secretKey = $this->deploymentConfig->get('crypt/key');
            $expectedToken = hash_hmac('sha256', $params['order_id'] . $params['invoice_id'], $secretKey);
            if (!hash_equals($expectedToken, $params['token'])) {
                throw new \Exception('Invalid token.');
            }
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->orderRepository->get($params['order_id']);
            try {
                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                $invoice = $this->invoiceRepository->get($params['invoice_id']);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {}

            if (isset($invoice)) {
                $finalOrderStatuses = [
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_COMPLETE,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                ];
                $finalInvoiceStatuses = [
                    \Magento\Sales\Model\Order\Invoice::STATE_PAID,
                    \Magento\Sales\Model\Order\Invoice::STATE_CANCELED
                ];
                if (!in_array($order->getState(), $finalOrderStatuses)
                    && !in_array($invoice->getState(), $finalInvoiceStatuses)) {
                    $token = $this->paymentService->generateHostedPaymentPage($order, $invoice);
                    // Load the page
                    $this->tokenProvider->setToken($token);
                }
            }

            return $this->pageFactory->create();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage() . "\n" . $exception->getTraceAsString());
            $resultRedirect = $this->redirectFactory->create();
            $resultRedirect->setRefererOrBaseUrl();
            return $resultRedirect;
        }
    }
}
