<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Block\Adminhtml;

class ConfirmationPopup extends \Magento\Backend\Block\Template
{
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vapewholesaleusa\PaymentRequest\Model\Config $config
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context                        $context,
        private readonly \Vapewholesaleusa\PaymentRequest\Model\Config $config,
        private readonly \Magento\Framework\Serialize\Serializer\Json  $json,
        array                                                          $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Modify and return the JavaScript layout as a JSON string.
     *
     * @return bool|string Serialized layout or false on failure.
     */
    public function getJsLayout(): bool|string
    {
        // Unserialize and modify the JS layout
        $layout = $this->json->unserialize(parent::getJsLayout());

        $layout['components']['payment-request-confirmation-popup']['title'] =
            __('Payment Request Confirmation');
        $layout['components']['payment-request-confirmation-popup']['content'] =
            __('Are you sure you want to send the payment request to the customer?');

        return $this->json->serialize($layout);
    }

    /**
     * Render HTML if enabled, otherwise return an empty string.
     *
     * @return string Rendered HTML or empty string.
     */
    public function toHtml(): string
    {
        if (!$this->config->isEnabled()) {
            return '';
        }

        return parent::toHtml();
    }
}
