<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Model;

class Config
{
    private const XML_PATH_ENABLED = 'vusa_payment_request/general/enabled';
    private const XML_PATH_STYLE_COLOR = 'vusa_payment_request/general/style_color';
    private const XML_PATH_MERCHANT_NAME= 'vusa_payment_request/general/merchant_name';

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Rootways\Authorizecim\Helper\Data $authorizeCimHelper
     */
    public function __construct(
        private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        private readonly \Rootways\Authorizecim\Helper\Data $authorizeCimHelper
    ) {
    }

    /**
     * Check if enabled
     *
     * @param mixed|null $store
     * @return string|null
     */
    public function isEnabled(mixed $store = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Gets the API Login ID for the payment gateway.
     *
     * @param mixed $store Optional store parameter.
     * @return string|null
     */
    public function getApiLoginId(mixed $store = null): ?string
    {
        return $this->authorizeCimHelper->getApiLoginId($store);
    }

    /**
     * Gets the transaction key for the payment gateway.
     *
     * @param mixed $store Optional store parameter.
     * @return string|null
     */
    public function getTransactionKey(mixed $store = null): ?string
    {
        return $this->authorizeCimHelper->getTransactionKey($store);
    }

    /**
     * Gets the gateway URL for the payment gateway.
     *
     * @param mixed $store Optional store parameter.
     * @return string|null
     */
    public function getGatewayUrl(mixed $store = null): ?string
    {
        return $this->authorizeCimHelper->getGatewayUrl($store);
    }

    /**
     * Gets the hosted gateway URL.
     *
     * @param mixed $store Optional store parameter.
     * @return string|null
     */
    public function getHostedGatewayUrl(mixed $store = null): ?string
    {
        return $this->authorizeCimHelper->getHostedGatewayUrl();
    }

    /**
     * Gets the style color from the configuration.
     *
     * @return string|null The configured style color or null if not set.
     */
    public function getStyleColor(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_STYLE_COLOR,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Gets the merchant name from the configuration.
     *
     * @return string|null The configured merchant name or null if not set.
     */
    public function getMerchantName(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MERCHANT_NAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
