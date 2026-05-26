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

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class RefundDataBuilder
 */
class RefundDataBuilder implements BuilderInterface
{
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $amount = SubjectReader::readAmount($buildSubject);
        $chargeId = str_replace('-refund', '', $payment->getTransactionId());
        $ccNumber = '';
        if ($payment->getCcLast4() != '') {
            $ccNumber = substr($payment->getCcLast4(), -4);
        }
        $order = $paymentDO->getOrder();
        $orderID = $order->getOrderIncrementId();
        $result['transactionRequest'] = [
            'transactionType' => 'refundTransaction',
            'amount' => $amount,
            'payment' => [
                'creditCard' => [
                    'cardNumber' => $ccNumber,
                    'expirationDate' => 'XXXX'
                ]
            ],
            'refTransId' => $chargeId,
            'order' => [
                'invoiceNumber' => $orderID
            ]
        ];

        return $result;
    }
}
