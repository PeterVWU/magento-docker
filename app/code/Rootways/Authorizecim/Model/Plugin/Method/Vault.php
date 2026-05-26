<?php
namespace Rootways\Authorizecim\Model\Plugin\Method;

use Magento\Payment\Gateway\Command;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Rootways\Authorizecim\Model\SampleConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Model\VaultPaymentInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\ObjectManagerInterface;

class Vault
{
    /**
     * @var Command\CommandManagerPoolInterface
     */
    private $commandManagerPool;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $tokenManagement;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @param Command\CommandManagerPoolInterface $commandManagerPool
     * @param PaymentTokenManagementInterface $tokenManagement
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param ObjectManagerInterface $objectManager
     * @param Json|null $jsonSerializer
     */
    public function __construct(
        Command\CommandManagerPoolInterface $commandManagerPool,
        PaymentTokenManagementInterface $tokenManagement,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        ObjectManagerInterface $objectManager,
        Json $jsonSerializer = null
    ) {
        $this->commandManagerPool = $commandManagerPool;
        $this->tokenManagement = $tokenManagement;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->objectManager = $objectManager;
        $this->jsonSerializer = $jsonSerializer ?: $this->objectManager->get(Json::class);
    }

    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function aroundOrder(
        \Magento\Vault\Model\Method\Vault $subject,
        callable $proceed,
        \Magento\Payment\Model\InfoInterface $payment,
        $amount
    ) {
        if (!empty($payment['method']) &&
            $payment['method'] == SampleConfigProvider::CC_VAULT_CODE
        ) {
            if (!$payment instanceof OrderPaymentInterface) {
                throw new \DomainException('Not implemented');
            }
            /** @var $payment OrderPaymentInterface */

            $this->attachTokenExtensionAttribute($payment);
            $this->attachCreditCardInfo($payment);

            $commandExecutor = $this->commandManagerPool->get(
                SampleConfigProvider::CODE
            );

            $commandExecutor->executeByCode(
                'orderonly_vault',
                $payment,
                [
                    'amount' => $amount
                ]
            );

            $payment->setMethod(SampleConfigProvider::CODE);

            return $subject;
        } else {
            return $proceed($payment, $amount);
        }
    }

    /**
     * Attaches token extension attribute.
     *
     * @param OrderPaymentInterface $orderPayment
     * @return void
     */
    private function attachTokenExtensionAttribute(OrderPaymentInterface $orderPayment)
    {
        $additionalInformation = $orderPayment->getAdditionalInformation();
        if (empty($additionalInformation[PaymentTokenInterface::PUBLIC_HASH])) {
            throw new \LogicException('Public hash should be defined');
        }

        $customerId = isset($additionalInformation[PaymentTokenInterface::CUSTOMER_ID]) ?
            $additionalInformation[PaymentTokenInterface::CUSTOMER_ID] : null;

        $publicHash = $additionalInformation[PaymentTokenInterface::PUBLIC_HASH];

        $paymentToken = $this->tokenManagement->getByPublicHash($publicHash, $customerId);

        if ($paymentToken === null) {
            throw new \LogicException("No token found");
        }

        $extensionAttributes = $this->getPaymentExtensionAttributes($orderPayment);
        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    /**
     * Attaches credit card info.
     *
     * @param OrderPaymentInterface $payment
     * @return void
     */
    private function attachCreditCardInfo(OrderPaymentInterface $payment): void
    {
        $paymentToken = $payment->getExtensionAttributes()
            ->getVaultPaymentToken();
        if ($paymentToken === null) {
            return;
        }

        $tokenDetails = $paymentToken->getTokenDetails();
        if ($tokenDetails === null) {
            return;
        }

        if (is_string($tokenDetails)) {
            $tokenDetails = $this->jsonSerializer->unserialize($paymentToken->getTokenDetails());
        }
        if (is_array($tokenDetails)) {
            $payment->addData($tokenDetails);
        }
    }

    /**
     * Returns Payment's extension attributes.
     *
     * @param OrderPaymentInterface $payment
     * @return \Magento\Sales\Api\Data\OrderPaymentExtensionInterface
     */
    private function getPaymentExtensionAttributes(OrderPaymentInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }
}
