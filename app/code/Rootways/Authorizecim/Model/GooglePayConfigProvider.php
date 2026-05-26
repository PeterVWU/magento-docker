<?php
/**
 * Authorize.net Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2025 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository;

/**
 * Class GooglePayConfigProvider
 */
class GooglePayConfigProvider implements ConfigProviderInterface
{
    const GOOGLE_PAY_CODE = 'rootways_authorizecim_option_googlepay';

    protected $methodCodes = [
        self::GOOGLE_PAY_CODE
    ];

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     * @param Repository $assetRepo
     */
    public function __construct(
        \Rootways\Authorizecim\Helper\Data $customHelper,
        Repository $assetRepo
    ) {
        $this->dataHelper = $customHelper;
        $this->assetRepo = $assetRepo;
    }

    public function getPaymentMarkSrc()
    {
        return $this->assetRepo->getUrl('Rootways_Authorizecim::images/googlepaytitle.png');
    }

    protected function getGooglePayCurrency()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getGooglePayCurrency($storeId);
    }

    protected function getGooglePayMerchantCountryCode()
    {
        $storeId = $this->dataHelper->getStoreId();
        return $this->dataHelper->getGooglePayMerchantCountryCode($storeId);
    }

    protected function getGPayEnv()
    {
        $environment = $this->dataHelper->getEnvironment($this->dataHelper->getStoreId());
        return $environment === 'sandbox' ? 'TEST' : 'PRODUCTION';
    }

    /**
     * Retrieve config object
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::GOOGLE_PAY_CODE => [
                    'gpay_env' => $this->getGPayEnv(),
                    'paymentMarkSrc' => $this->getPaymentMarkSrc(),
                    'merchant_name' =>  $this->dataHelper->getConfig('payment/rootways_authorizecim_option_googlepay/merchant_name'),
                    'g_id' => $this->dataHelper->getConfig('payment/rootways_authorizecim_option_googlepay/gateway_id'),
                    'googlePayCcAvailableCcTypes' => $this->dataHelper->getGooglePayCcAvailableCardTypes(),
                    'googlepayCurrency' =>  $this->getGooglePayCurrency(),
                    'googlepayMerchantCountryCode' =>  $this->getGooglePayMerchantCountryCode()
                ],
            ]
        ];
    }
}
