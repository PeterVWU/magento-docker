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
 * Class CaptureTransactionDataBuilder
 */
class CaptureTransactionDataBuilder implements BuilderInterface
{
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $amount = SubjectReader::readAmount($buildSubject);
        $chargeId = str_replace('-capture', '', $payment->getCcTransId());
        $result['transactionRequest'] = [
            'transactionType' => 'priorAuthCaptureTransaction',
            'amount' => $amount,
            'refTransId' => $chargeId
        ];

        return $result;
    }
}
