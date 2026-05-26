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

use \Magento\Backend\Block\Template\Context;
use \Rootways\Authorizecim\Helper\Data as DataHelper;
use \Rootways\Authorizecim\Model\Request\Api as ApiRequest;

class ApiAuthentication extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var DataHelper
     */
    protected $helper;

    /**
     * @var ApiRequest
     */
    protected $apiRequest;

    /**
     * @param Context $context
     * @param DataHelper $helper
     * @param ApiRequest $apiRequest
     */
    public function __construct(
        Context $context,
        DataHelper $helper,
        ApiRequest $apiRequest,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->apiRequest = $apiRequest;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        $result = $this->apiRequest->apiAuthentication();
        $status = 0;
        if (isset($result['messages']['message']['code']) &&
            $result['messages']['message']['code'] == "I00001") {
            $status = 1;
        }
        if ($status == 0) {
            $html .= <<<HTML
        <div style="margin-top: 5px;color: red;">Failed</div>
HTML;
        } else {
            $html .= <<<HTML
        <div style="margin-top:  7px;color: #3bb53b;font-weight: bold;">API authenticated successfully</div>
HTML;
        }
        return $html;
    }
}
