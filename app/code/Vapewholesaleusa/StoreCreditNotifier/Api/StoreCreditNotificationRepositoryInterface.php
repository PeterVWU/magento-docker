<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Api;

/**
 * Repository interface for managing Store Credit Notifications.
 */
interface StoreCreditNotificationRepositoryInterface
{
    /**
     * Save a store credit notification.
     *
     * @param \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
     * @return \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface
     */
    public function save(
        \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
    ): \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface;

    /**
     * Get a store credit notification by ID.
     *
     * @param int $id
     * @return \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface
     */
    public function getById(int $id): \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface;

    /**
     * Delete a store credit notification.
     *
     * @param \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
     * @return void
     */
    public function delete(
        \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
    ): void;

    /**
     * Retrieve notifications by scheduled date and status.
     *
     * @param string $scheduledDate
     * @param string $status
     * @return \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface[]
     */
    public function getByScheduledDate(string $scheduledDate, string $status): array;
}
