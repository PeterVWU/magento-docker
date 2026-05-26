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
use Rootways\Authorizecim\Helper\Data;
use Rootways\Authorizecim\Observer\EcheckDataAssignObserver;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class EcheckTransactionDataBuilder
 */
class EcheckTransactionDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * EcheckTransactionDataBuilder constructor.
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
        $result['transactionRequest'] = [
            'transactionType' => $this->customHelper->getEcheckPaymentAction(),
            'amount' => SubjectReader::readAmount($buildSubject),
            'payment' => [
                'bankAccount' => [
                    'accountType' => $payment->getAdditionalInformation(EcheckDataAssignObserver::ACCOUNT_TYPE),
                    'routingNumber' => $payment->getAdditionalInformation(EcheckDataAssignObserver::ROUTING_NUMBER),
                    'accountNumber' => $payment->getAdditionalInformation(EcheckDataAssignObserver::ACCOUNT_NUMBER),
                    'nameOnAccount' => $payment->getAdditionalInformation(EcheckDataAssignObserver::ACCOUNT_NAME)
                ]
            ]
        ];

        return $result;
    }
}
