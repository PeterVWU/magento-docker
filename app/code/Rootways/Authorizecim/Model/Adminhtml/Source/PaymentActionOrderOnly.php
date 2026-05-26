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
namespace Rootways\Authorizecim\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentActionOrderOnly.
 */
class PaymentActionOrderOnly implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Possible actions on order place.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture (Sale)'),
            ],
            [
                'value' => AbstractMethod::ACTION_ORDER,
                'label' => __('Order (Save Info Only)'),
            ],
        ];
    }
}
