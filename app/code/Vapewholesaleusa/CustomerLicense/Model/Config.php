<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CustomerLicense\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    const XML_ENABLE = 'customer_license/general/enabled';
    const XML_NOTIFICATION_EMAILS = 'customer_license/general/notification_emails';
    const XML_NOTIFICATION_DAYS = 'customer_license/general/notification_days';
    const XML_EMAIL_TEMPLATE = 'customer_license/general/email_template';


    /**
     * Construct
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Is enabled
     *
     * @param int|null $store
     * @return bool
     */
    public function isEnabled(?int $store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Notification Emails
     *
     * @param int|null $store
     * @return array
     */
    public function getNotificationEmails(?int $store = null)
    {
        $emails = $this->scopeConfig->getValue(
            self::XML_NOTIFICATION_EMAILS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$emails) {
            return [];
        }

        return array_map('trim', explode(',', $emails));
    }


    /**
     * Notification Days
     *
     * @param int|null $store
     * @return array
     */
    public function getNotificationDays(?int $store = null)
    {
        $days = $this->scopeConfig->getValue(
            self::XML_NOTIFICATION_DAYS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if (!$days) {
            return [];
        }

        return array_map('trim', explode(',', $days));
    }

    /**
     * Email Template
     *
     * @param int|null $store
     * @return mixed
     */
    public function getEmailTemplate(?int $store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store);
    }
}
