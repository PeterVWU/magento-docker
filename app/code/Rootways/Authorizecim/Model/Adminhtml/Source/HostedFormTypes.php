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

use Magento\Framework\Option\ArrayInterface;

/**
 * Class HostedFormTypes
 */
class HostedFormTypes implements ArrayInterface
{
    /**
     * Possible CcLogoLocation types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'onsite',
                'label' => __('On-site'),
            ],
            [
                'value' => 'iframe',
                'label' => __('Hosted iFrame Popup'),
            ],
        ];
    }
}
