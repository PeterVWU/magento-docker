<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\ViewModel;

class ConfigProvider implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     */
    public function __construct(
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config $config
    ) {
    }

    /**
     * Check if enabled
     *
     * @param mixed|null $store
     * @return string|null
     */
    public function isEnabled($store = null): string|null
    {
        return $this->config->isEnabled($store);
    }
}
