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
namespace Rootways\Authorizecim\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class SampleConfigProvider
 */
class SampleConfigProvider implements ConfigProviderInterface
{
    const CODE = 'rootways_authorizecim_option';
    const CC_VAULT_CODE = 'rootways_authorizecim_option_cc_vault';

    /** @var int */
    const DUPLICATE_WINDOW = 5;

    protected $methodCodes = [
        self::CODE
    ];

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Payment\Model\Config $paymentConfig,
        \Rootways\Authorizecim\Helper\Data $customHelper
    ) {
        $this->customerSession = $customerSession;
        $this->_paymentConfig = $paymentConfig;
        $this->customHelper = $customHelper;
    }

    public function getCcMonths()
    {
        return $this->_paymentConfig->getMonths();
    }

    public function getCcYears()
    {
        $years = [];
        $years = $this->_paymentConfig->getYears();
        return $years;
    }

    protected function getCcAvailableCcTypes()
    {
        return $this->customHelper->getCcAvailableCardTypes();
    }

    /**
     * @return string
     */
    protected function getEnvironment()
    {
        return $this->customHelper->getEnvironment($this->customHelper->getStoreId());
    }

    /**
     * @return boolean
     */
    protected function enableAcceptjs()
    {
        return (bool)$this->customHelper->enableAcceptjs();
    }

    /**
     * @return string
     */
    protected function getApiLoginId()
    {
        $storeId = $this->customHelper->getStoreId();
        return $this->customHelper->getApiLoginId($storeId);
    }

    /**
     * @return string
     */
    protected function getApiClientKey()
    {
        $storeId = $this->customHelper->getStoreId();
        return $this->customHelper->getApiClientKey($storeId);
    }

    /**
     * @return boolean
     */
    protected function getEnableCaptcha()
    {
        return $this->customHelper->getEnableCaptcha();
    }

    /**
     * @return string
     */
    protected function getCaptchaSiteKey()
    {
        return $this->customHelper->getCaptchaSiteKey();
    }

    /**
     * @return bool
     */
    protected function isAlwaysSaveCc()
    {
        return (bool) $this->customHelper->getConfig('payment/rootways_authorizecim_option/always_save_cc');
    }

    /**
     * Retrieve config object
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'storedCards' => '',
                    'isCustLoggedIn' => $this->customerSession->isLoggedIn(),
                    'availableCardTypes' => $this->getCcAvailableCcTypes(),
                    'ccMonths' => $this->getCcMonths(),
                    'ccYears' => $this->getCcYears(),
                    'ccVaultCode' => self::CC_VAULT_CODE,
                    'environment' => $this->getEnvironment(),
                    'enableAcceptjs' => $this->enableAcceptjs(),
                    'enableAcceptUijs' => (bool)$this->customHelper->enableAcceptUijs(),
                    'enableAcceptUiBtnTxt' => $this->customHelper->enableAcceptUiButTxt(),
                    'enableAcceptUiHeaderTxt' => $this->customHelper->enableAcceptUiHeaderTxt(),
                    'apiLoginId' => $this->getApiLoginId(),
                    'apiClientKey' => $this->getApiClientKey(),
                    'iscaptchaenable' => $this->getEnableCaptcha(),
                    'captchasitekey' => $this->getCaptchaSiteKey(),
                    'topNote' => $this->customHelper->getCCMethodTopNote(),
                    'cclogolocation' => (bool)$this->customHelper->getConfig('payment/rootways_authorizecim_option/cclogo_location'),
                    'ccAlwaysSave' => $this->isAlwaysSaveCc()
                ],
            ]
        ];
    }
}
