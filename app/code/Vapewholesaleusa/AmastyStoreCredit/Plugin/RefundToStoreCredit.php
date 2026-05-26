<?php

namespace Vapewholesaleusa\AmastyStoreCredit\Plugin;

use Amasty\StoreCredit\Api\Data\SalesFieldInterface;

class RefundToStoreCredit
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $currency;

    /**
     * @var \Amasty\StoreCredit\Api\ManageCustomerStoreCreditInterface
     */
    private $manageCustomerStoreCredit;

    /**
     * @var \Amasty\StoreCredit\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $adminSession;

    private ?float $scBaseAmount = null;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Pricing\PriceCurrencyInterface $currency,
        \Amasty\StoreCredit\Api\ManageCustomerStoreCreditInterface $manageCustomerStoreCredit,
        \Amasty\StoreCredit\Model\ConfigProvider $configProvider,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Backend\Model\Auth\Session $adminSession
    ) {
        $this->request = $request;
        $this->currency = $currency;
        $this->manageCustomerStoreCredit = $manageCustomerStoreCredit;
        $this->configProvider = $configProvider;
        $this->orderRepository = $orderRepository;
        $this->adminSession = $adminSession;
    }

    public function beforeRefund(
        \Magento\Sales\Model\Service\CreditmemoService $subject,
        \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo,
                                                       $offlineRequested = false
    ) {
        if ($this->configProvider->isEnabled()) {
            // capture base amount for afterRefund()
            $this->scBaseAmount = (float) $creditmemo->getAmstorecreditBaseAmount();

            if ($amount = $creditmemo->getAmstorecreditAmount()) {
                $order = $creditmemo->getOrder();
                $order->setAmstorecreditRefundedAmount($order->getAmstorecreditRefundedAmount() + $amount);
                $order->setAmstorecreditRefundedBaseAmount(
                    $order->getAmstorecreditRefundedBaseAmount() + $creditmemo->getAmstorecreditBaseAmount()
                );
            }
            if ($shippingAmount = $creditmemo->getData(SalesFieldInterface::AMSC_SHIPPING_AMOUNT)) {
                $order->setData(
                    SalesFieldInterface::AMSC_SHIPPING_AMOUNT_REFUNDED,
                    $order->getData(SalesFieldInterface::AMSC_SHIPPING_AMOUNT_REFUNDED) + $shippingAmount
                );
            }
        }

        return [$creditmemo, $offlineRequested];
    }

    public function afterRefund(
        \Magento\Sales\Model\Service\CreditmemoService $subject,
        \Magento\Sales\Model\Order\Creditmemo $result
    ) {
        if ($this->configProvider->isEnabled()) {
            $amount = $result->getAmstorecreditBaseAmount();
            if ($amount === null || $amount === '' || (float)$amount <= 0) {
                $amount = $this->scBaseAmount;
            }
            $amount = (float)$amount;

            if ($amount > 0) {
                $admin = $this->adminSession->getUser();
                $adminName = ($admin && $admin->getId())
                    ? $admin->getFirstname() . ' ' . $admin->getLastname()
                    : null;
                $comment = $adminName ? sprintf('by %s using Admin Panel', $adminName) : '';

                $this->manageCustomerStoreCredit->addOrSubtractStoreCredit(
                    $result->getCustomerId(),
                    $amount,
                    \Amasty\StoreCredit\Model\History\MessageProcessor::CREDIT_MEMO_REFUND,
                    [$this->orderRepository->get($result->getOrderId())->getIncrementId()],
                    $result->getStoreId(),
                    $comment,
                    false,
                    (int)$result->getOrderId()
                );
            }
        }

        $this->scBaseAmount = null;

        return $result;
    }
}
