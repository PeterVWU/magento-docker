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
use Rootways\Authorizecim\Helper\Data as customHelper;
use Magento\Framework\View\Asset\Repository;

/**
 * Class SampleConfigProvider
 */
class VisaPaymentConfigProvider implements ConfigProviderInterface
{
    const CODE = 'rootways_authorizecim_option_visa';

    protected $methodCodes = [
        self::CODE
    ];

    /**
     * @var customHelper
     */
    protected $customHelper;

    /**
     * @var Repository
     */
    protected $assetRepo;

    /**
     * @param customHelper $customHelper
     * @param Repository $assetRepo
     */
    public function __construct(
        customHelper $customHelper,
        Repository $assetRepo
    ) {
        $this->customHelper = $customHelper;
        $this->assetRepo = $assetRepo;
    }

    public function getVisaCheckoutLabelImg()
    {
        return $this->assetRepo->getUrl('Rootways_Authorizecim::images/visa-checkout-label.png');
    }

    /**
     * @return string
     */
    protected function getEnvironment()
    {
        return $this->customHelper->getEnvironment($this->customHelper->getStoreId());
    }

    /**
     * Retrieve config object
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'environment' => $this->getEnvironment(),
                    'visaCheckoutCurrency' => $this->customHelper->getVisaCheckoutCurrency(),
                    'apiKey' => $this->customHelper->getVisaCheckoutMethodApiKey(),
                    'getLocale' =>  $this->customHelper->getVisaCheckoutLocale(),
                    'topNote' => $this->customHelper->getVisaTopNote(),
                    'visaCheckoutLabelImg' => $this->getVisaCheckoutLabelImg()
                ],
            ]
        ];
    }
}
