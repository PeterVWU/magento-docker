<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Cron;

/**
 * Cron job for generating notification queues based on customer store credit.
 */
class QueueGenerator
{
    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Vapewholesaleusa\StoreCreditNotifier\Model\StoreCreditNotificationFactory $storeCreditNotificationFactory
     * @param \Vapewholesaleusa\StoreCreditNotifier\Api\StoreCreditNotificationRepositoryInterface $repository
     * @param \Vapewholesaleusa\StoreCreditNotifier\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private readonly \Magento\Framework\App\ResourceConnection $resourceConnection,
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Model\StoreCreditNotificationFactory $storeCreditNotificationFactory,
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Api\StoreCreditNotificationRepositoryInterface $repository,
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Model\Config $config,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * Execute the cron job.
     *
     * @return void
     * @throws \JsonException
     * @throws \DateMalformedIntervalStringException
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $batchSize = $this->config->getEmailsPerDay();

        if ($batchSize <= 0) {
            $this->logger->info('Invalid batch size configuration.');
            return;
        }

        // Fetch customers with store credit > 0
        $query = $connection->select()
            ->from(['sc' => 'amasty_store_credit'], ['customer_id', 'store_credit'])
            ->join(
                ['ce' => 'customer_entity'],
                'sc.customer_id = ce.entity_id',
                ['email', 'firstname', 'lastname']
            )
            ->where('sc.store_credit > ?', 0);

        $customers = $connection->fetchAll($query);

        if (empty($customers)) {
            return;
        }

        $totalCustomers = count($customers);
        $customersChunks = array_chunk($customers, $batchSize);
        $currentDate = new \DateTime();

        foreach ($customersChunks as $index => $batch) {
            // Calculate scheduled date for the current batch
            $scheduledDate = (clone $currentDate)->add(new \DateInterval('P' . $index . 'D'))->format('Y-m-d');

            // Serialize customer data for the batch
            $serializedData = json_encode($batch, JSON_THROW_ON_ERROR);

            $notification = $this->storeCreditNotificationFactory->create();
            $notification->setDataSerialized($serializedData)
                ->setScheduledDate($scheduledDate)
                ->setStatus('pending');

            try {
                $this->repository->save($notification);
            } catch (\Exception $e) {
                $this->logger->error('Failed to save notification: ' . $e->getMessage());
            }
        }

        $this->logger->info(
            'Generated ' . count($customersChunks) . ' notification batches for ' . $totalCustomers . ' customers.'
        );
    }
}
