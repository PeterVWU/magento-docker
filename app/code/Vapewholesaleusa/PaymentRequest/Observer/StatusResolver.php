<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Observer;

class StatusResolver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @param \Magento\Sales\Model\Order\StatusResolver $statusResolver
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     */
    public function __construct(
        private readonly \Magento\Sales\Model\Order\StatusResolver     $statusResolver,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config $config
    ) {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        if ($order->getPayment()->getMethod() === 'mageworx_ordereditor_payment_method' &&
            $invoice->getRequestedCaptureCase() === \Magento\Sales\Model\Order\Invoice::NOT_CAPTURE &&
            $this->config->isEnabled() == '1') {
            $state = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $status = $this->statusResolver->getOrderStatusByState($order, $state);
            $order->setState($state);
            $order->setStatus($status);
        }
    }
}
