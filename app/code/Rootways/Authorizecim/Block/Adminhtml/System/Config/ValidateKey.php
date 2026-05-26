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
namespace Rootways\Authorizecim\Block\Adminhtml\System\Config;

class ValidateKey extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('Rootways_Authorizecim::system/config/validate_key.phtml');
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //if (some_condition_here_which_decide_whether_to_display_button) {
            $element->setData('scope', null);
            return parent::render($element);
        //}

        return '';
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->getAjaxUrl(),
                'field_mapping' => str_replace('"', '\\"', json_encode($this->_getFieldMapping()))
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Get validate key url
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->_urlBuilder->getUrl(
            'rootwaysauthorizecim/system_config/validatekey',
            [
                'form_key' => $this->getFormKey(),
            ]
        );
    }

    /**
     * Returns configuration fields required to validate Rootways extension license key
     *
     * @return array
     */
    protected function _getFieldMapping()
    {
        return ['license' => 'rootways_authorizecim_general_licencekey'];
    }
}
