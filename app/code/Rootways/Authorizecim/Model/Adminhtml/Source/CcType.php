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

class CcType extends \Magento\Payment\Model\Source\Cctype
{
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'AE', 'DI', 'JCB', 'DN', 'OT'];
    }
}
