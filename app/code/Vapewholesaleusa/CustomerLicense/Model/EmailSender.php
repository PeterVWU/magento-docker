<?php
declare(strict_types=1);

namespace Vapewholesaleusa\CustomerLicense\Model;

use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Vapewholesaleusa\CustomerLicense\Model\Config;

class EmailSender
{
    /**
     * Construct
     *
     * @param TransportBuilder $transportBuilder
     * @param Config $config
     */
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly Config $config
    ) {
    }

    /**
     * Send Email
     *
     * @param $customer
     * @param $licenseType
     * @param $expirationDate
     * @return void
     * @throws MailException|\Magento\Framework\Exception\LocalizedException
     */
    public function send($customer, $licenseType, $expirationDate)
    {
        $transport = $this->transportBuilder
            ->setTemplateIdentifier($this->config->getEmailTemplate((int)$customer->getStoreId()))
            ->setTemplateOptions([
                'area'  => 'frontend',
                'store' => $customer->getStoreId()
            ])
            ->setTemplateVars([
                'license' => $licenseType,
                'customer_data' => [
                    'customer_id' => (int) $customer->getId(),
                    'expire_date' => (new \DateTime($expirationDate))->format('M. d, Y')
                ]
            ])
            ->setFromByScope('general', (int) $customer->getStoreId())
            ->addTo($customer->getEmail());
        foreach ($this->config->getNotificationEmails((int) $customer->getStoreId()) as $email) {
            $transport->addBcc($email);
        }
        $transport->getTransport()->sendMessage();
    }
}
