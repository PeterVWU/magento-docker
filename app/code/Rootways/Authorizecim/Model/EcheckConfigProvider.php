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
 * Class EcheckConfigProvider
 */
class EcheckConfigProvider implements ConfigProviderInterface
{
    const CODE = 'rootways_authorizecim_echeck';

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

    /**
     * Retrieve config object
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'topNote' => $this->customHelper->getCCMethodTopNote(),
                    'accountTypes' => $this->customHelper->getAchAccountTypes()
                ],
            ]
        ];
    }
}
