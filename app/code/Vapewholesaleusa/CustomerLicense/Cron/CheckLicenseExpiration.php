<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CustomerLicense\Cron;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Psr\Log\LoggerInterface;
use Vapewholesaleusa\CustomerLicense\Model\Config;
use Vapewholesaleusa\CustomerLicense\Model\EmailSender;

class CheckLicenseExpiration
{

    private const BUSINESS_LICENSE = 'business_license_expiration';
    private const TOBACCO_LICENSE = 'tobacco_license_expiration';
    private const PAGE_SIZE = 500;

    /**
     * Construct
     *
     * @param CollectionFactory $customerCollectionFactory
     * @param Config $config
     * @param EmailSender $emailSender
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CollectionFactory $customerCollectionFactory,
        private readonly Config $config,
        private readonly EmailSender $emailSender,
        private readonly LoggerInterface $logger
    ){
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $targetDates = [];

        foreach ($this->config->getNotificationDays() as $day) {
            $targetDates[] = date('Y-m-d 00:00:00', strtotime("{$day} days"));
        }
        try {
            $this->processLicenses(array_unique($targetDates));
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Process Licenses
     *
     * @param array $targetDates
     * @return void
     */
    private function processLicenses(array $targetDates): void
    {
        $page = 1;

        $collection = $this->customerCollectionFactory->create();

        $collection->addAttributeToSelect([
            self::BUSINESS_LICENSE,
            self::TOBACCO_LICENSE
        ]);

        $collection->addAttributeToFilter([
            [
                'attribute' => self::BUSINESS_LICENSE,
                'in' => $targetDates
            ],
            [
                'attribute' => self::TOBACCO_LICENSE,
                'in' => $targetDates
            ]
        ]);

        $collection->setPageSize(self::PAGE_SIZE);

        $lastPage = $collection->getLastPageNumber();

        while ($page <= $lastPage) {

            $collection->setCurPage($page);
            $collection->load();
            $collection->walk([$this, 'processCustomer'], [$targetDates]);
            $collection->clear();
            $page++;
        }
    }

    /**
     * Process Customer
     *
     * @param $customer
     * @param array $targetDates
     * @return void
     */
    public function processCustomer($customer, array $targetDates): void
    {
        $businessExpiration = $customer->getData(self::BUSINESS_LICENSE);
        $tobaccoExpiration = $customer->getData(self::TOBACCO_LICENSE);

        if ($businessExpiration && in_array($businessExpiration, $targetDates)) {
            $this->emailSender->send(
                $customer,
                'Business License',
                $businessExpiration
            );
        }

        if ($tobaccoExpiration && in_array($tobaccoExpiration, $targetDates)) {
            $this->emailSender->send(
                $customer,
                'Tobacco License',
                $tobaccoExpiration
            );
        }
    }
}
