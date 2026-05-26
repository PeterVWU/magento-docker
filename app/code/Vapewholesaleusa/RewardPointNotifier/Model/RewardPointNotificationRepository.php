<?php

declare(strict_types=1);

namespace Vapewholesaleusa\RewardPointNotifier\Model;

/**
 * Repository for managing Reward Point Notifications.
 */
class RewardPointNotificationRepository implements \Vapewholesaleusa\RewardPointNotifier\Api\RewardPointNotificationRepositoryInterface
{
    /**
     * Constructor.
     *
     * @param \Vapewholesaleusa\RewardPointNotifier\Model\ResourceModel\RewardPointNotification $resource
     * @param \Vapewholesaleusa\RewardPointNotifier\Model\RewardPointNotificationFactory $rewardPointNotificationFactory
     * @param \Vapewholesaleusa\RewardPointNotifier\Model\ResourceModel\RewardPointNotification\CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly \Vapewholesaleusa\RewardPointNotifier\Model\ResourceModel\RewardPointNotification $resource,
        private readonly \Vapewholesaleusa\RewardPointNotifier\Model\RewardPointNotificationFactory $rewardPointNotificationFactory,
        private readonly \Vapewholesaleusa\RewardPointNotifier\Model\ResourceModel\RewardPointNotification\CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Save a reward point notification.
     *
     * @param \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
     * @return \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
    ): \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface {
        try {
            $this->resource->save($rewardPointNotification);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to save reward point notification: %1', $e->getMessage())
            );
        }

        return $rewardPointNotification;
    }

    /**
     * Get a reward point notification by ID.
     *
     * @param int $id
     * @return \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface
    {
        $notification = $this->rewardPointNotificationFactory->create();
        $this->resource->load($notification, $id);

        if (!$notification->getId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Reward Point Notification with ID "%1" does not exist.', $id)
            );
        }

        return $notification;
    }

    /**
     * Delete a reward point notification.
     *
     * @param \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Vapewholesaleusa\RewardPointNotifier\Api\Data\RewardPointNotificationInterface $rewardPointNotification
    ): void {
        try {
            $this->resource->delete($rewardPointNotification);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to delete reward point notification: %1', $e->getMessage())
            );
        }
    }

    /**
     * Retrieve reward point notifications by scheduled date and status.
     *
     * @param string $scheduledDate
     * @param string $status
     * @return \Vapewholesaleusa\RewardPointNotifier\Model\RewardPointNotification[]
     */
    public function getByScheduledDate(string $scheduledDate, string $status): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('scheduled_date', $scheduledDate);
        $collection->addFieldToFilter('status', $status);

        return $collection->getItems();
    }
}
