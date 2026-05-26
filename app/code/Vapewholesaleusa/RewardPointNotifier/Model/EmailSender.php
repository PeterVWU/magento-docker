<?php

declare(strict_types=1);

namespace Vapewholesaleusa\RewardPointNotifier\Model;

/**
 * Handles email sending for Reward Point Notifications.
 */
class EmailSender
{
    /**
     * Constructor.
     *
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Vapewholesaleusa\RewardPointNotifier\Model\Config $config
     */
    public function __construct(
        private readonly \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        private readonly \Psr\Log\LoggerInterface $logger,
        private readonly \Vapewholesaleusa\RewardPointNotifier\Model\Config $config
    ) {
    }

    /**
     * Send an email to a customer.
     *
     * @param array $customer
     * @return void
     */
    public function send(array $customer): void
    {
        if (!$this->config->isEmailEnabled()) {
            return;
        }

        try {
            $templateVars = [
                'customer_name' => $customer['firstname'] . ' ' . $customer['lastname'],
                'reward_point_amount' => number_format((float)$customer['points'], 2),
                'store_name' => $this->config->getStoreName(),
                'store_phone' => $this->config->getStorePhone(),
                'store_address' => $this->config->getStoreAddress(),
                'store_email' => $this->config->getStoreEmail(),
                'store_hours' => $this->config->getStoreHours(),
                'store_frontend_name' => $this->config->getStoreName(),
            ];

            $this->transportBuilder
                ->setTemplateIdentifier($this->config->getEmailTemplate())
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => 1,
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($this->config->getEmailIdentity(), 1)
                ->addTo($customer['email'], $customer['firstname'] . ' ' . $customer['lastname']);

            // Handle email copies
            $copyTo = $this->config->getEmailCopyTo();
            $copyMethod = $this->config->getEmailCopyMethod();

            if (!empty($copyTo)) {
                if ($copyMethod === 'bcc') {
                    foreach ($copyTo as $email) {
                        $this->transportBuilder->addBcc($email);
                    }
                } elseif ($copyMethod === 'copy') {
                    foreach ($copyTo as $email) {
                        $this->sendCopyTo($email, $templateVars);
                    }
                }
            }

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email to ' . $customer['email'] . ': ' . $e->getMessage());
        }
    }

    /**
     * Send a copy email to a single recipient.
     *
     * @param string $email
     * @param array $templateVars
     * @return void
     */
    private function sendCopyTo(string $email, array $templateVars): void
    {
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($this->config->getEmailTemplate())
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => 1,
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($this->config->getEmailIdentity(), 1)
                ->addTo($email);

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Failed to send copy email to ' . $email . ': ' . $e->getMessage());
        }
    }
}
