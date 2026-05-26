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
namespace Rootways\Authorizecim\Block;

use Magento\Framework\Phrase;

/**
 * Admin order transatoin details for ACH/eCheck orders
 *
 * InfoEcheck
 */
class InfoEcheck extends Info
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        switch ($field) {
            case 'cc_numlast4':
                return __('Account Number');
            default:
                return parent::getLabel($field);
        }
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getValueView($field, $value)
    {
        if ($field == 'cc_type') {
            if ($this->customHelper->getCcTypeNameByCode($value)) {
                $value = $this->customHelper->getCcTypeNameByCode($value);
            }
        }
        if ($field == 'avs_response_code') {
            if (isset($this->_avsCode[trim($value)])) {
                $value = $value . ' ('.$this->_avsCode[trim($value)].')';
            }
        }
        if ($field == 'cvd_response_code') {
            if (is_string($value)) {
                if (isset($this->_cvvCode[trim($value)])) {
                    $value = $value . ' ('.$this->_cvvCode[trim($value)].')';
                }
            } else {
                $value = '';
            }
        }
        return $value;
    }
}
