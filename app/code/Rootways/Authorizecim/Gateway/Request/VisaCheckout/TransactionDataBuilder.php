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
namespace Rootways\Authorizecim\Gateway\Request\VisaCheckout;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Rootways\Authorizecim\Observer\VisaCheckoutDataAssignObserver;
use Rootways\Authorizecim\Helper\Data;

/**
 * Class TransactionDataBuilder
 */
class TransactionDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->customHelper = $helper;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $amount = SubjectReader::readAmount($buildSubject);
        $transactionType = $this->customHelper->getVisaCheckoutPaymentAction();
        $currencyCode = $this->customHelper->getVisaCheckoutCurrency();
        $dataV = $payment->getAdditionalInformation(VisaCheckoutDataAssignObserver::VISA_DATA_VAL);
        $dataK = $payment->getAdditionalInformation(VisaCheckoutDataAssignObserver::VISA_DATA_KEY);
        $cId = $payment->getAdditionalInformation(VisaCheckoutDataAssignObserver::VISA_CALL_ID);

        $result['transactionRequest'] = [
            'transactionType' => $transactionType,
            'amount'   => $amount,
            'currencyCode' => $currencyCode,
            'payment' => [
                'opaqueData' => [
                    'dataDescriptor' => 'COMMON.VCO.ONLINE.PAYMENT',
                    'dataValue' => $dataV,
                    'dataKey' => $dataK,
                ]
            ],
            'callId' => $cId
        ];

        return $result;
    }
}
