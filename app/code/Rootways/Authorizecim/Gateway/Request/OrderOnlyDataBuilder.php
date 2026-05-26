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
use Rootways\Authorizecim\Helper\Data as CustomHlper;
use Rootways\Authorizecim\Model\Request\Api as CustomApi;
use Rootways\Authorizecim\Observer\DataAssignObserver;

/**
 * Class OrderOnlyDataBuilder
 * @package Rootways\Authorizecim\Gateway\Request
 */
class OrderOnlyDataBuilder implements BuilderInterface
{
    /**
     * @var CustomHlper
     */
    protected $customHelper;

    /**
     * @var CustomApi
     */
    protected $customApi;

    /**
     * TestOrderOnlyBuilder constructor.
     * @param CustomHlper $customHelper
     * @param CustomApi $customApi
     */
    public function __construct(
        CustomHlper $customHelper,
        CustomApi $customApi
    ) {
        $this->customHelper = $customHelper;
        $this->customApi = $customApi;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $billingAddress = $order->getBillingAddress();
        $email = !empty($billingAddress->getEmail()) ? $this->customHelper->subStrFun($billingAddress->getEmail(), 254) : '';
        $customerProfileId = $this->customApi->getCustomerProfileIdForOrderOnlyPaymentAction($email);

        $result['customerProfileId'] = $customerProfileId;
        $result['paymentProfile']['billTo'] = $this->customHelper->getFormattedAddress($billingAddress, 'billTo');

        if ($payment->getAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_DESCRIPTOR) != '' &&
            ($this->customHelper->enableAcceptjs() || $this->customHelper->enableAcceptUijs())
        ) {
            $result['paymentProfile']['payment'] = [
                'opaqueData' => [
                    'dataDescriptor' => $payment->getAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_DESCRIPTOR),
                    'dataValue' => $payment->getAdditionalInformation(DataAssignObserver::ACCEPTJS_DATA_VALUE)
                ]
            ];
        } else {
            if ($this->customHelper->getEnableCaptcha() != 0 &&
                $this->customHelper->checkAdmin() != 'adminhtml'
               ) {
                $CAPTCHA = $payment->getAdditionalInformation(DataAssignObserver::G_CAPTCHA);
                $verifyC = $this->customHelper->verifyGC($CAPTCHA);
                if ($verifyC != '') {
                    throw new \Magento\Framework\Exception\LocalizedException(__($verifyC));
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

            $result['paymentProfile']['payment'] = [
                'creditCard' => [
                    'cardNumber' => $accountNum,
                    'expirationDate' => $expDate,
                ]
            ];
        }

        $result['paymentProfile']['defaultPaymentProfile'] = 0;
        $result['validationMode'] = 'liveMode';

        return $result;
    }
}
