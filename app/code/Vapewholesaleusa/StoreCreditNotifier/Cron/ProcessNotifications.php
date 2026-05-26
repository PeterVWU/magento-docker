<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Cron;

/**
 * Cron job for processing store credit notifications.
 */
class ProcessNotifications
{
    /**
     * Constructor.
     *
     * @param \Vapewholesaleusa\StoreCreditNotifier\Api\StoreCreditNotificationRepositoryInterface $repository
     * @param \Vapewholesaleusa\StoreCreditNotifier\Model\EmailSender $emailSender
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Vapewholesaleusa\StoreCreditNotifier\Model\Config $config
     */
    public function __construct(
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Api\StoreCreditNotificationRepositoryInterface $repository,
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Model\EmailSender $emailSender,
        private readonly \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        private readonly \Psr\Log\LoggerInterface $logger,
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Model\Config $config
    ) {
    }

    /**
     * Execute the cron job.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $currentDate = $this->dateTime->gmtDate('Y-m-d');

            // Fetch notifications scheduled for today with status 'pending'.
            $notifications = $this->repository->getByScheduledDate($currentDate, 'pending');

            if (empty($notifications)) {
                return;
            }

            foreach ($notifications as $notification) {
                $this->processNotification($notification);
            }

            $this->logger->info('Processed ' . count($notifications) . ' notifications for ' . $currentDate . '.');
        } catch (\Exception $e) {
            $this->logger->error('Error processing notifications: ' . $e->getMessage());
        }
    }

    /**
     * Process a single notification.
     *
     * @param \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $notification
     * @return void
     */
    private function processNotification(
        \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $notification
    ): void {
        try {
            $data = json_decode($notification->getDataSerialized(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($data as $customer) {
                $this->emailSender->send($customer);
            }

            // Update notification status to 'completed'.
            $notification->setStatus('completed');
            $this->repository->save($notification);
        } catch (\Exception $e) {
            $notification->setStatus('error');
            $this->repository->save($notification);
            $this->logger->error(
                'Failed to process notification ID ' . $notification->getId() . ': ' . $e->getMessage()
            );
        }
    }
}
