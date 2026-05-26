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

class CaptchaType implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('Disabled'),
            ],
            [
                'value' => 3,
                'label' => __('reCAPTCHA v3 Invisible (Recommended)'),
            ]
        ];
    }
}
