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
namespace Rootways\Authorizecim\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Rootways\Authorizecim\Observer\DataAssignObserver;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class TransactionDataBuilder
 */
class TransactionDataBuilder implements BuilderInterface
{
    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * TransactionDataBuilder constructor.
     * @param \Rootways\Authorizecim\Helper\Data $helper
     */
    public function __construct(
        \Rootways\Authorizecim\Helper\Data $helper
    ) {
        $this->customHelper = $helper;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $amount = SubjectReader::readAmount($buildSubject);
        $transactionType = $this->customHelper->getPaymentAction();
        if (!empty($payment->getAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_DESCRIPTOR)) &&
            ($this->customHelper->enableAcceptjs() || $this->customHelper->enableAcceptUijs())
        ) {
            $dataDescriptor = $payment->getAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_DESCRIPTOR);
            $opaqueData = $payment->getAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_VALUE);
            $result['transactionRequest'] = [
                'transactionType' => $transactionType,
                'amount'   => $amount,
                'payment' => [
                    'opaqueData' => [
                        'dataDescriptor' => $dataDescriptor,
                        'dataValue' => $opaqueData
                    ]
                ]
            ];
        } else {
            if ($this->customHelper->getEnableCaptcha() != 0 &&
                $this->customHelper->checkAdmin() != 'adminhtml'
               ) {
                $CAPTCHA = $payment->getAdditionalInformation(DataAssignObserver::G_CAPTCHA);
                $verifyC = $this->customHelper->verifyGC($CAPTCHA);
                if ($verifyC != '') {
                    throw new LocalizedException(__($verifyC));
                }
            }
            $accountNum = $payment->getData(DataAssignObserver::CC_NUMBER);
            $dt = \DateTime::createFromFormat('Y', $payment->getData(DataAssignObserver::CC_EXP_YEAR));
            $expYear = trim($payment->getData(DataAssignObserver::CC_EXP_YEAR));
            if ($dt) {
                $expYear = $dt->format('Y');
            }
            $expMonth = sprintf("%02d", $payment->getData(DataAssignObserver::CC_EXP_MONTH));
            $expDate = $expYear.'-'.$expMonth;
            $cvv = $payment->getData(DataAssignObserver::CC_CID);

            $result['transactionRequest'] = [
                'transactionType' => $transactionType,
                'amount'   => $amount,
                'payment' => [
                    'creditCard' => [
                        'cardNumber' => $accountNum,
                        'expirationDate' => $expDate,
                        'cardCode' => $cvv
                    ]
                ]
            ];
        }

        return $result;
    }
}
