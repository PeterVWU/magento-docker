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
 * Class Local
 */
class Locale implements ArrayInterface
{
    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'en_US',
                'label' => 'American English',
            ],
            [
                'value' => 'en_CA',
                'label' => 'Canadian English'
            ],
            [
                'value' => 'fr_CA',
                'label' => 'Canadian French'
            ],
            [
                'value' => 'en_AU',
                'label' => 'Australian English'
            ]
        ];
    }
}
