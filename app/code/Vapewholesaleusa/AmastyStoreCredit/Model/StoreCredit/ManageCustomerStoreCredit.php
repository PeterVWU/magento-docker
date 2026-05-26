<?php

declare(strict_types=1);

namespace Vapewholesaleusa\AmastyStoreCredit\Model\StoreCredit;

/**
 * Class ManageCustomerStoreCredit
 * @package Vapewholesaleusa\AmastyStoreCredit\Model\StoreCredit
 */
class ManageCustomerStoreCredit extends \Amasty\StoreCredit\Model\StoreCredit\ManageCustomerStoreCredit
{
    /**
     * ManageCustomerStoreCredit constructor.
     * @param \Amasty\StoreCredit\Api\StoreCreditRepositoryInterface $storeCreditRepository
     * @param \Amasty\StoreCredit\Model\StoreCredit\ResourceModel\StoreCredit $storeCredit
     * @param \Amasty\StoreCredit\Model\History\HistoryRepository $historyRepository
     * @param \Amasty\StoreCredit\Model\ConfigProvider $configProvider
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Amasty\StoreCredit\Utils\Email $email
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private \Amasty\StoreCredit\Api\StoreCreditRepositoryInterface $storeCreditRepository,
        private \Amasty\StoreCredit\Model\StoreCredit\ResourceModel\StoreCredit $storeCredit,
        private \Amasty\StoreCredit\Model\History\HistoryRepository $historyRepository,
        private \Amasty\StoreCredit\Model\ConfigProvider $configProvider,
        private \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        private \Amasty\StoreCredit\Utils\Email $email,
        private \Magento\Store\Model\StoreManagerInterface $storeManager,
        private \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        private \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $storeCreditRepository,
            $storeCredit,
            $historyRepository,
            $configProvider,
            $customerRepository,
            $email,
            $storeManager,
            $priceCurrency
        );
    }

    /**
     * @param int $customerId
     * @param float $amount
     * @param int $action
     * @param array $actionData
     * @param int $storeId
     * @param string $message
     * @param bool $visibleForCustomer
     * @param int|null $orderId
     * @return \Amasty\StoreCredit\Api\Data\StoreCreditInterface|void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addOrSubtractStoreCredit(
        $customerId,
        $amount,
        $action,
        $actionData = [],
        $storeId = 0,
        $message = '',
        bool $visibleForCustomer = false,
        ?int $orderId = null
    ) {
        $storeCredit = $this->storeCreditRepository->getByCustomerId($customerId);
        $currencyCode = $this->storeManager->getStore($storeId)->getCurrentCurrencyCode();
        $storeCreditAmount = $this->priceCurrency->convertAndRound(
            $storeCredit->getStoreCredit(),
            $storeId,
            $currencyCode
        );
        $amountRound = $this->priceCurrency->convertAndRound(
            (float)$amount,
            $storeId,
            $currencyCode
        );
        $newStoreCredit = $storeCreditAmount + $amountRound;
        if ($newStoreCredit < 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Store Credit couldn\'t be less than zero.'));
        }
        $storeCredit->setStoreCredit($newStoreCredit);
        try {
            $this->storeCredit->save($storeCredit);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Unable to save store credit. Error: %1', $e->getMessage())
            );
        }

        try {
            $actionData = array_values($actionData);
            /** @var \Amasty\StoreCredit\Model\History\History $history */
            $history = $this->historyRepository->historyCreate();
            $history->setCustomerHistoryId($this->historyRepository->getNextCustomerHistoryId($customerId))
                ->setCustomerId($customerId)
                ->setIsDeduct($amount < 0)
                ->setDifference(abs($amount))
                ->setStoreCreditBalance($storeCredit->getStoreCredit())
                ->setStoreId($storeId)
                ->setAction($action)
                ->setActionData(json_encode($actionData))
                ->setMessage($message)
                ->setOrderId($orderId);
            $history->setIsVisibleForCustomer($visibleForCustomer);
            $history = $this->historyRepository->save($history);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Unable to save store credit history. Error: %1', $e->getMessage())
            );
        }

        // @phpstan-ignore-next-line
        try {
            if ($this->configProvider->isEmailEnabled()
                && in_array($action, $this->configProvider->getEmailActions())
            ) {
                $customer = $this->customerRepository->getById($customerId);
                $actionAdd = $actionRemove = $actionCreditMemo = $actionOrderPay = $actionOrderCancel = false;
                $actionBuyStoreCredit = false;
                switch ($action) {
                    case \Amasty\StoreCredit\Model\History\MessageProcessor::ADMIN_BALANCE_CHANGE_PLUS:
                        $actionAdd = true;
                        break;
                    case \Amasty\StoreCredit\Model\History\MessageProcessor::ADMIN_BALANCE_CHANGE_MINUS:
                        $actionRemove = true;
                        break;
                    case \Amasty\StoreCredit\Model\History\MessageProcessor::CREDIT_MEMO_REFUND:
                        $actionCreditMemo = true;
                        break;
                    case \Amasty\StoreCredit\Model\History\MessageProcessor::ORDER_PAY:
                        $actionOrderPay = true;
                        break;
                    case \Amasty\StoreCredit\Model\History\MessageProcessor::ORDER_CANCEL:
                        $actionOrderCancel = true;
                        break;
                    case \Amasty\StoreCredit\Model\History\MessageProcessor::BUY_STORE_CREDIT_PRODUCT:
                        $actionBuyStoreCredit = true;
                        break;
                }
                $vars = compact(
                    'actionAdd',
                    'actionRemove',
                    'actionCreditMemo',
                    'actionOrderPay',
                    'actionOrderCancel',
                    'actionBuyStoreCredit'
                );
                $vars['customerName'] = $customer->getFirstname();
                $vars['storeCredit'] = $this->priceCurrency->convertAndFormat(
                    abs($history->getDifference()),
                    false,
                    2,
                    null,
                    $currencyCode
                );
                $vars['newBalance'] = $this->priceCurrency->convertAndFormat(
                    $history->getStoreCreditBalance(),
                    false,
                    2,
                    null,
                    $currencyCode
                );
                if (!empty($actionData[0])) {
                    $vars['orderId'] = $actionData[0];
                }
                if (!empty($message) && $visibleForCustomer) {
                    $vars['message'] = $message;
                }

                $this->email->sendEmail(
                    [
                        'email' => $customer->getEmail(),
                        'name' => $customer->getFirstname()
                    ],
                    \Amasty\StoreCredit\Model\ConfigProvider::EMAIL_TEMPLATE,
                    $vars,
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    $this->configProvider->getEmailSender(),
                    $this->configProvider->getEmailReplyTo(),
                    $storeId
                );
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
