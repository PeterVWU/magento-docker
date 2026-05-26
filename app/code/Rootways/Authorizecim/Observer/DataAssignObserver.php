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
namespace Rootways\Authorizecim\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Rootways\Authorizecim\Helper\Data;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    const CC_NUMBER = 'cc_number';
    const CC_CID = 'cc_cid';
    const CC_TYPE = 'cc_type';
    const CC_EXP_MONTH = 'cc_exp_month';
    const CC_EXP_YEAR = 'cc_exp_year';
    const SAVE_CARD = 'is_active_payment_token_enabler';
    const ACCEPTJS_DATA_VALUE = 'data_value';
    const ACCEPTJS_DATA_DESCRIPTOR = 'data_descriptor';
    const G_CAPTCHA = 'captcha_string';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::CC_NUMBER,
        self::CC_CID,
        self::CC_TYPE,
        self::CC_EXP_MONTH,
        self::CC_EXP_YEAR,
        self::ACCEPTJS_DATA_VALUE,
        self::ACCEPTJS_DATA_DESCRIPTOR,
    ];

    protected $extraData = [
        self::SAVE_CARD,
        self::G_CAPTCHA,
        self::ACCEPTJS_DATA_VALUE,
        self::ACCEPTJS_DATA_DESCRIPTOR,
    ];

    /**
     * @var Data
     */
    protected $customHlper;

    /**
     * DataAssignObserver constructor.
     * @param Data $customHlper
     */
    public function __construct(
        Data $customHlper
    ) {
        $this->customHlper = $customHlper;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $observer->getData('data');
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $payment = $observer->getData('payment_model');
        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $payment->setData($additionalInformationKey, $additionalData[$additionalInformationKey]);
            }
        }

        foreach ($this->extraData as $extraDataKey) {
            if (isset($additionalData[$extraDataKey])) {
                $payment->setAdditionalInformation(
                    $extraDataKey,
                    $additionalData[$extraDataKey]
                );
            } else {
                $payment->unsAdditionalInformation($extraDataKey);
            }
        }

        if ((bool)$this->customHlper->getConfig('payment/rootways_authorizecim_option/always_save_cc') &&
            (bool)$this->customHlper->getConfig('payment/rootways_authorizecim_option_cc_vault/active')
        ) {
            $payment->setAdditionalInformation(self::SAVE_CARD, 1);
        }
    }
}
