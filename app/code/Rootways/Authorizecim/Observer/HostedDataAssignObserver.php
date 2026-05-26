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

/**
 * Class HostedDataAssignObserver
 */
class HostedDataAssignObserver extends AbstractDataAssignObserver
{
    const SAVE_CARD = 'is_active_payment_token_enabler';
    const ACCEPTJS_DATA_VALUE = 'data_value';
    const ACCEPTJS_DATA_DESCRIPTOR = 'data_descriptor';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::SAVE_CARD,
        self::ACCEPTJS_DATA_VALUE,
        self::ACCEPTJS_DATA_DESCRIPTOR
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $data_1 = $observer->getData('data');

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            } else {
                $paymentInfo->unsAdditionalInformation($additionalInformationKey);
            }
        }
    }
}
