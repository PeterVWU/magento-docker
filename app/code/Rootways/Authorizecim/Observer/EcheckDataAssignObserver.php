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
 * Class EcheckDataAssignObserver
 */
class EcheckDataAssignObserver extends AbstractDataAssignObserver
{
    const ACCOUNT_NAME = 'name_on_account';
    const ACCOUNT_NUMBER = 'account_number';
    const ROUTING_NUMBER = 'routing_number';
    const ACCOUNT_TYPE = 'account_type';
    const SAVE_CARD = 'is_active_payment_token_enabler';
    const G_CAPTCHA = 'captcha_string';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::ACCOUNT_NAME,
        self::ACCOUNT_NUMBER,
        self::ROUTING_NUMBER,
        self::ACCOUNT_TYPE,
        self::SAVE_CARD,
        self::G_CAPTCHA,
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

        foreach ($this->additionalInformationList as $extraDataKey) {
            if (isset($additionalData[$extraDataKey])) {
                $payment->setAdditionalInformation(
                    $extraDataKey,
                    $additionalData[$extraDataKey]
                );
            } else {
                $payment->unsAdditionalInformation($extraDataKey);
            }
        }
    }
}
