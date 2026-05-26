<?php
namespace Rootways\Authorizecim\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;

class CaptureStrategyCommand implements CommandInterface
{
    /**
     * Authorize.net authorize and capture command
     */
    const SALE = 'sale';

    /**
     * Authorize.net capture command
     */
    const PRE_AUTH_CAPTURE = 'settlement';

    /**
     * Chase vault capture command
     */
    const VAULT_CAPTURE = 'vault_capture';

    /**
     * @var Command\CommandPoolInterface
     */
    private $commandPool;

    /**
     * @param Command\CommandPoolInterface $commandPool
     */
    public function __construct(
        Command\CommandPoolInterface $commandPool
    ) {
        $this->commandPool = $commandPool;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $commandSubject)
    {
        /** @var PaymentDataObjectInterface $paymentObject */
        $paymentObject = SubjectReader::readPayment($commandSubject);

        /** @var Order\Payment $payment */
        $payment = $paymentObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        if ($payment->getMethodInstance()->getConfigPaymentAction() === AbstractMethod::ACTION_ORDER) {
            return $this->commandPool
                ->get('orderauthorize')
                ->execute($commandSubject);
        }

        if ($payment instanceof Order\Payment
            && $payment->getAuthorizationTransaction()
        ) {
            return $this->commandPool
                ->get(self::PRE_AUTH_CAPTURE)
                ->execute($commandSubject);
        }

        return $this->commandPool
            ->get(self::SALE)
            ->execute($commandSubject);
    }
}
