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
 * Class HostedPaymentConfigProvider
 */
class HostedPaymentConfigProvider implements ConfigProviderInterface
{
    const CODE = 'rootways_authorizecim_option_hosted';

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
    protected $dataHelper;

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
        $this->dataHelper = $customHelper;
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
        return $this->dataHelper->getCcAvailableCardTypes();
    }

    protected function getEnvironment()
    {
        return $this->dataHelper->getEnvironment($this->dataHelper->getStoreId());
    }

    /**
     * @return string
     */
    protected function getHostedGatewayUrl()
    {
        return $this->dataHelper->getHostedGatewayUrl();
    }

    /**
     * @return string
     */
    protected function getApiLoginId()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getApiLoginId($storeId);
    }

    /**
     * @return string
     */
    protected function getApiClientKey()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getApiClientKey($storeId);
    }

    /**
     * @return string
     */
    protected function hostedFormOrderBtnTxt()
    {
        return $this->dataHelper->hostedFormOrderBtnTxt();
    }

    /**
     * Retrieve config object
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isCustLoggedIn' => $this->customerSession->isLoggedIn(),
                    'availableCardTypes' => $this->getCcAvailableCcTypes(),
                    'environment' => $this->getEnvironment(),
                    'hostedGatewayUrl' => $this->getHostedGatewayUrl(),
                    'apiLoginId' => $this->getApiLoginId(),
                    'apiClientKey' => $this->getApiClientKey(),
                    'hostedFormOrderBtnTxt' => $this->hostedFormOrderBtnTxt(),
                    'topNote' => $this->dataHelper->getHostedMethodTopNote()
                ],
            ]
        ];
    }
}
