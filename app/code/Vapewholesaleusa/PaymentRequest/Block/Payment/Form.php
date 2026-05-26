<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Block\Payment;

class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     * @param \Vapewholesaleusa\PaymentRequest\Model\TokenProvider $tokenProvider
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context                      $context,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config        $config,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\TokenProvider $tokenProvider,
        array                                                                 $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Get token for use in the template.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->tokenProvider->getToken();
    }

    /**
     * Gets the hosted gateway URL.
     *
     * @return string
     */
    public function getHostedGatewayUrl(): string
    {
        return $this->config->getHostedGatewayUrl();
    }
}
