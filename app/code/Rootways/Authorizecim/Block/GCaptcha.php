<?php
/**
 * Authorizecim Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Block;

class GCaptcha extends \Magento\Framework\View\Element\Template
{
    /*
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $helper;

    /**
     * $param \Magento\Framework\View\Element\Template\Context $context
     * $param \Rootways\Authorizecim\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Rootways\Authorizecim\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        if ($this->helper->getEnableCaptcha() == 1 &&
            $this->helper->getConfig('payment/rootways_authorizecim_option/captcha_js')
           ) {
            $this->pageConfig->addRemotePageAsset(
                'https://www.google.com/recaptcha/api.js',
                'js',
                ['attributes' => 'async defer']
            );
        }

        if ($this->helper->getEnableCaptcha() == 3) {
            $this->pageConfig->addRemotePageAsset(
                'https://www.google.com/recaptcha/api.js?render='.$this->helper->getCaptchaSiteKey(),
                'js',
                ['attributes' => 'async defer']
            );
        }
    }
}
