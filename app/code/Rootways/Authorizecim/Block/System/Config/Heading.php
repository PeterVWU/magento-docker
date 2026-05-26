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
namespace Rootways\Authorizecim\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Registry;
use Rootways\Authorizecim\Helper\Data;

class Heading extends Field
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @param Context $context
     * @param Data $helper
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '';
        if ($this->_helper->act() != '') {
            $status = base64_decode($this->_helper->act());
            $html .= <<<HTML
        <div style="margin-top: 5px;color: red;">$status</div>
HTML;
        } else {
            $a = base64_decode('RXh0ZW5zaW9uIEFjdGl2YXRlZC4=');
            $html .= <<<HTML
        <div style="margin-top:  7px;color: #3bb53b;font-weight: bold;">$a</div>
HTML;
        }
        return $html;
    }
}
