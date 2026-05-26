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

/**
 * Class AddressDataBuilder
 */
class HostedTransactionDataBuilder implements BuilderInterface
{
    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * HostedTransactionDataBuilder constructor.
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
        $transactionType = $this->customHelper->getHostedPaymentAction();
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

        return $result;
    }
}
