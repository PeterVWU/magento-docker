<?php

namespace Vapewholesaleusa\OrderSource\Model\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Data
{
    const XML_PATH_ENABLE = 'ordersource/general/enable';
    const XML_PATH_MAX_ORDER = 'ordersource/general/order_limit';
    const XML_PATH_ASSIGMENT_ALGORITHM = 'ordersource/general/order_source_assigment';
    const XML_PATH_ORDER_SOURCE = 'ordersource/general/order_sourcing';
    const XML_PATH_SHIPPING_SOURCE = 'ordersource/general/shipping_sourcing';
    const XML_PATH_STOCK_SOURCE = 'ordersource/general/stock_sourcing';
    const XML_PATH_CLEANUP_PERIOD = 'ordersource/general/cleanup_period';
    const XML_PATH_FALLBACK_SOURCE = 'ordersource/general/fallback_source';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
    public function isModuleEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLE);
    }

    /**
     * @return mixed
     */
    public function getMaxOrder()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_MAX_ORDER);
    }

    /**
     * @return mixed
     */
    public function getAssigmentAlgorithm()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ASSIGMENT_ALGORITHM);
    }

    /**
     * @return mixed
     */
    public function getOrderSourcing()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ORDER_SOURCE);
    }

    /**
     * @return mixed
     */
    public function getShippingSourcing()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHIPPING_SOURCE);
    }

    /**
     * @return mixed
     */
    public function getStockSourcing()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STOCK_SOURCE);
    }

    /**
     * @return mixed
     */
    public function getCleanUpPeriod()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CLEANUP_PERIOD);
    }

    /**
     * @return mixed
     */
    public function getFallbackSource()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FALLBACK_SOURCE);
    }
}
