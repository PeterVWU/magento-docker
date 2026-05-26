<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Model;

/**
 * Class for managing module configuration.
 */
class Config
{
    public const XML_PATH_ENABLE = 'store_credit_notifier/general/is_enabled';
    public const XML_PATH_EMAILS_PER_DAY = 'store_credit_notifier/general/per_day';
    public const XML_PATH_EMAIL_ENABLED = 'store_credit_notifier/email/enabled';
    public const XML_PATH_EMAIL_IDENTITY = 'store_credit_notifier/email/identity';
    public const XML_PATH_EMAIL_TEMPLATE = 'store_credit_notifier/email/template';
    public const XML_PATH_EMAIL_COPY_TO = 'store_credit_notifier/email/copy_to';
    public const XML_PATH_EMAIL_COPY_METHOD = 'store_credit_notifier/email/copy_method';

    private const XML_PATH_STORE_PHONE = 'general/store_information/phone';
    private const XML_PATH_STORE_NAME = 'general/store_information/name';
    private const XML_PATH_STORE_ADDRESS = 'general/store_information/address';
    private const XML_PATH_STORE_EMAIL = 'trans_email/ident_general/email';
    private const XML_PATH_STORE_HOURS = 'general/store_information/hours';

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if the module is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE);
    }

    /**
     * Get the maximum number of emails to send per day.
     *
     * @return int
     */
    public function getEmailsPerDay(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_EMAILS_PER_DAY);
    }

    /**
     * Check if email notifications are enabled.
     *
     * @return bool
     */
    public function isEmailEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_EMAIL_ENABLED);
    }

    /**
     * Get email sender identity.
     *
     * @return string
     */
    public function getEmailIdentity(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_IDENTITY);
    }

    /**
     * Get email template ID.
     *
     * @return string
     */
    public function getEmailTemplate(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE);
    }

    /**
     * Get list of email recipients for a copy.
     *
     * @return array
     */
    public function getEmailCopyTo(): array
    {
        $emails = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_COPY_TO);
        return $emails ? array_map('trim', explode(',', $emails)) : [];
    }

    /**
     * Get email copy method.
     *
     * @return string
     */
    public function getEmailCopyMethod(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_EMAIL_COPY_METHOD);
    }

    /**
     * Get store phone.
     *
     * @return string|null
     */
    public function getStorePhone(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STORE_PHONE);
    }

    /**
     * Get store name.
     *
     * @return string|null
     */
    public function getStoreName(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STORE_NAME);
    }

    /**
     * Get store address.
     *
     * @return string|null
     */
    public function getStoreAddress(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STORE_ADDRESS);
    }

    /**
     * Get store email.
     *
     * @return string|null
     */
    public function getStoreEmail(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STORE_EMAIL);
    }

    /**
     * Get store hours.
     *
     * @return string|null
     */
    public function getStoreHours(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_STORE_HOURS);
    }
}
