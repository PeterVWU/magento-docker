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
 * Class TokenDataBuilder
 */
class TokenDataBuilder implements BuilderInterface
{
    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * TokenDataBuilder constructor.
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
        $extensionAttributes = $payment->getExtensionAttributes();
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        $custProId = $this->customHelper->formatedCustomerId($paymentToken->getGatewayToken());
        $paymentId = $this->customHelper->getPaymentIdByToken($paymentToken);
        $result['transactionRequest'] = [
            'transactionType' => $transactionType,
            'amount'   => $amount,
            'profile' => [
                'customerProfileId' => $custProId,
                'paymentProfile' => [
                    'paymentProfileId' => $paymentId
                ]
            ]
        ];

        return $result;
    }
}
