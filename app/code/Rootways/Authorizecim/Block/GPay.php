<?php
/**
 * Authorizecim Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2025 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Block;

class GPay extends \Magento\Framework\View\Element\Template
{
    protected function _construct()
    {
        $this->pageConfig->addRemotePageAsset(
            'https://pay.google.com/gp/p/js/pay.js',
            'js',
            ['attributes' => 'async defer']
        );
    }
}
