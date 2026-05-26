<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Model\Email\Container;

class PaymentRequestIdentity extends \Magento\Sales\Model\Order\Email\Container\InvoiceIdentity
{
    /**
     * Configuration paths
     */
    const XML_PATH_EMAIL_COPY_METHOD = 'vusa_payment_request/email/copy_method';
    const XML_PATH_EMAIL_COPY_TO = 'vusa_payment_request/email/copy_to';
    const XML_PATH_EMAIL_IDENTITY = 'vusa_payment_request/email/identity';
    const XML_PATH_EMAIL_GUEST_TEMPLATE = 'vusa_payment_request/email/guest_template';
    const XML_PATH_EMAIL_TEMPLATE = 'vusa_payment_request/email/template';
    const XML_PATH_EMAIL_ENABLED = 'vusa_payment_request/email/enabled';

    /**
     * Is email enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EMAIL_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Return email copy_to list
     *
     * @return array|bool
     */
    public function getEmailCopyTo(): bool|array
    {
        $data = $this->getConfigValue(self::XML_PATH_EMAIL_COPY_TO, $this->getStore()->getStoreId());
        if (!empty($data)) {
            return array_map('trim', explode(',', $data));
        }
        return false;
    }

    /**
     * Return copy method
     *
     * @return mixed
     */
    public function getCopyMethod(): mixed
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_COPY_METHOD, $this->getStore()->getStoreId());
    }

    /**
     * Return guest template id
     *
     * @return mixed
     */
    public function getGuestTemplateId(): mixed
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $this->getStore()->getStoreId());
    }

    /**
     * Return template id
     *
     * @return mixed
     */
    public function getTemplateId(): mixed
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_TEMPLATE, $this->getStore()->getStoreId());
    }

    /**
     * Return email identity
     *
     * @return mixed
     */
    public function getEmailIdentity(): mixed
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_IDENTITY, $this->getStore()->getStoreId());
    }
}
