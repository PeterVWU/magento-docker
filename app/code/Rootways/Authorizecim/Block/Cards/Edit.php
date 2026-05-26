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
namespace Rootways\Authorizecim\Block\Cards;

use Magento\Customer\Helper\Address;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\ObjectManager;

/**
 * Class Edit
 */
class Edit extends \Magento\Directory\Block\Data
{
    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $paymentConfig;

    protected $buildUrl;

    /**
     * Edit constructor.
     * @param Context $context
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param array $data
     * @param Address|null $addressHelper
     */
    public function __construct(
        Context $context,
        \Rootways\Authorizecim\Helper\Data $customHelper,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        array $data = [],
        Address $addressHelper = null
    ) {
        $this->customHelper = $customHelper;
        $this->paymentConfig = $paymentConfig;
        $this->buildUrl = $context->getUrlBuilder();
        $data['addressHelper'] = $addressHelper ?: ObjectManager::getInstance()->get(Address::class);
        $data['directoryHelper'] = $directoryHelper;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
    }

    /**
     * Get add card form URL.
     *
     * @return string
     */
    public function getEditCardUrl()
    {
        return $this->buildUrl->getUrl('rootways_authorizecim/vaultcards/save');
    }

    /**
     * Retrieve expiry months.
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] = __('Month');
            $months = array_merge($months, $this->paymentConfig->getMonths());
            $this->setData('cc_months', $months);
        }
        return $months;
    }

    /**
     * @return array|mixed|null
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (!($years)) {
            $years = $this->paymentConfig->getYears();
            $years = [0 => __('Year')] + $years;
            $this->setData('cc_years', $years);
        }

        return $years;
    }

    /**
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->paymentConfig->getCcTypes();
        $availableTypes = explode(',', $this->customHelper->getConfig('payment/rootways_authorizecim_option/cctypes'));
        if ($availableTypes) {
            foreach ($types as $code => $name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }

        return $types;
    }

    /**
     * @param $regionCode
     * @param $countryCode
     * @return mixed
     */
    public function getRegionIdByCode($regionCode, $countryCode)
    {
        $region = $this->customHelper->loadRegionByCode($regionCode, $countryCode);

        return $region->getId();
    }

    /**
     * Get config value.
     *
     * @param string $path
     * @return string|null
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
