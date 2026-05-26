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
namespace Rootways\Authorizecim\Block\Form;

class Cc extends \Magento\Payment\Block\Form\Cc
{
    protected $_template = 'Rootways_Authorizecim::form/cc.phtml';

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customhelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Rootways\Authorizecim\Helper\Data $customhelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Rootways\Authorizecim\Helper\Data $customhelper,
        array $data = []
    ) {
        $this->customhelper = $customhelper;
        parent::__construct($context, $paymentConfig, $data);
    }

    public function enableAcceptjs()
    {
        return $this->customhelper->enableAcceptjs();
    }

    public function getEnvironment()
    {
        $storeId = $this->customhelper->getStoreId();
        return $this->customhelper->getEnvironment($storeId);
    }

    public function getApiLoginId()
    {
        $storeId = $this->customhelper->getStoreId();
        return $this->customhelper->getApiLoginId($storeId);
    }

    public function getApiClientKey()
    {
        $storeId = $this->customhelper->getStoreId();
        return $this->customhelper->getApiClientKey($storeId);
    }
}
