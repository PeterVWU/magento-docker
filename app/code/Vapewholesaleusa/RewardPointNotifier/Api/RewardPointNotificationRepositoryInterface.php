<?php

declare(strict_types=1);

namespace Vapewholesaleusa\RewardPointNotifier\Api;

/**
 * Repository interface for managing Reward Point Notifications.
 */
interface RewardPointNotificationRepositoryInterface
{
    /**
     * Save a reward point notification.
     *
     * @param \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
     * @return \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface
     */
    public function save(
        \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
    ): \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface;

    /**
     * Get a reward point notification by ID.
     *
     * @param int $id
     * @return \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface
     */
    public function getById(int $id): \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface;

    /**
     * Delete a reward point notification.
     *
     * @param \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
     * @return void
     */
    public function delete(
        \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
    ): void;

    /**
     * Retrieve notifications by scheduled date and status.
     *
     * @param string $scheduledDate
     * @param string $status
     * @return \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface[]
     */
    public function getByScheduledDate(string $scheduledDate, string $status): array;
}
