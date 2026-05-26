<?php
/**
 * Authorize.net Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Rootways\Authorizecim\Helper\Data as CustomHelper;
use Rootways\Authorizecim\Model\Request\Api as CustomApi;
use Rootways\Authorizecim\Observer\DataAssignObserver;

/**
 * Payment Details Handler
 */
class PaymentDetailsHandler implements HandlerInterface
{
    const TRANSID = 'TRANSID';
    const ACCOUNTNUMBER = 'ACCOUNTNUMBER';

    /**
     * @var CustomHelper
     */
    private $customHelper;

    /**
     * @var CustomApi
     */
    private $customApi;

    /**
     * PaymentDetailsHandler constructor.
     * @param CustomHelper $customHelper
     * @param CustomApi $customApi
     */
    public function __construct(
        CustomHelper $customHelper,
        CustomApi $customApi
    ) {
        $this->customHelper = $customHelper;
        $this->customApi = $customApi;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        $transId = $response['transactionResponse']['transId'];
        $payment->setTransactionId($transId);
        $payment->setCcTransId($transId);
        $payment->setLastTransId($transId);
        $payment->setAdditionalInformation('transaction_id', $transId);
        if (!empty($response['transactionResponse']['authorization'])) {
            $payment->setAdditionalInformation('auth_code', $response['transactionResponse']['authorization']);
        }
        if (!empty($response['transactionResponse']['accountNumber']) &&
            !is_array($response['transactionResponse']['accountNumber'])
        ) {
            $payment->setCcLast4(substr($response['transactionResponse']['accountNumber'], -4));
        }
        if (!empty($response['transactionResponse']['accountType'])) {
            $ccType = $this->customHelper->getCcTypeCodeByName($response['transactionResponse']['accountType']);
            if ($ccType) {
                $payment->setCcType($ccType);
                $payment->setAdditionalInformation('cc_type', $ccType);
            }
        }
        $payment->setCcStatus($response['transactionResponse']['responseCode']);

        $payment->unsAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_VALUE);
        $payment->unsAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_DESCRIPTOR);
        $payment->unsAdditionalInformation(DataAssignObserver::G_CAPTCHA);
        $payment->unsAdditionalInformation('is_save_info_transaction');

        $payment->setIsTransactionClosed($this->shouldCloseTransaction());
        $closed = $this->shouldCloseParentTransaction($payment);
        $payment->setShouldCloseParentTransaction($closed);

        if ($response['transactionResponse']['responseCode'] == '4') {
            $payment->setIsTransactionPending(true);
        }
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction(): bool
    {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $payment): bool
    {
        return false;
    }
}
