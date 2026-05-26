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

/**
 * Class PaymentMode.
 */
class PaymentMode implements \Magento\Framework\Option\ArrayInterface
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
                'value' => 0,
                'label' => __('Test Mode'),
            ],
            [
                'value' => 1,
                'label' => __('Live Mode'),
            ],
        ];
    }
}
