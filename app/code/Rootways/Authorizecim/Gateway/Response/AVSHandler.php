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
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class AVSHandler
 */
class AVSHandler implements HandlerInterface
{
    const AVSRESPCODE = 'avsResultCode';
    const CVV2RESPCODE = 'cvvResultCode';
    const RESCCNUMLAST4 = 'accountNumber';

    private $additionalInformationMapping = [
        'avs_response_code' => self::AVSRESPCODE,
        'cvd_response_code' => self::CVV2RESPCODE,
        'cc_numlast4' => self::RESCCNUMLAST4
    ];

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        if (!empty($response['transactionResponse']['avsResultCode'])) {
            $payment->setCcAvsStatus($response['transactionResponse']['avsResultCode']);
        }
        if (!empty($response['transactionResponse']['cvvResultCode'])) {
            $payment->setCcCidStatus($response['transactionResponse']['cvvResultCode']);
        }

        foreach ($this->additionalInformationMapping as $informationKey => $responseKey) {
            if (isset($response['transactionResponse'][$responseKey])) {
                $payment->setAdditionalInformation($informationKey, $response['transactionResponse'][$responseKey]);
            }
        }
    }
}
