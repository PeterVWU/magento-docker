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
namespace Rootways\Authorizecim\Gateway\Request\GooglePay;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Rootways\Authorizecim\Observer\GooglePayDataAssignObserver;

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
        $order = $paymentDO->getOrder();
        $orderID = $order->getOrderIncrementId();
        $amount = SubjectReader::readAmount($buildSubject);
        $transactionType = $this->customHelper->getGooglePayPaymentAction();
        $currencyCode = $this->customHelper->getConfig('payment/rootways_authorizecim_option_googlepay/currency');

        $result['transactionRequest'] = [
            'transactionType' => $transactionType,
            'amount'   => $amount,
            'currencyCode' => $currencyCode,
            'payment' => [
                'opaqueData' => [
                    'dataDescriptor' => 'COMMON.GOOGLE.INAPP.PAYMENT',
                    'dataValue' => $payment->getAdditionalInformation(GooglePayDataAssignObserver::PAYMENT_METHOD_NONCE)
                ]
            ],
            'order' => [
                'invoiceNumber' => $orderID
            ]
        ];

        return $result;
    }
}
