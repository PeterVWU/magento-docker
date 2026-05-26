<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Model;

/**
 * Repository for managing store credit notifications.
 */
class StoreCreditNotificationRepository implements \Vapewholesaleusa\StoreCreditNotifier\Api\StoreCreditNotificationRepositoryInterface
{
    /**
     * Constructor.
     *
     * @param \Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel\StoreCreditNotification $resource
     * @param \Vapewholesaleusa\StoreCreditNotifier\Model\StoreCreditNotificationFactory $storeCreditNotificationFactory
     * @param \Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel\StoreCreditNotification\CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel\StoreCreditNotification $resource,
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Model\StoreCreditNotificationFactory $storeCreditNotificationFactory,
        private readonly \Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel\StoreCreditNotification\CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Save a store credit notification.
     *
     * @param \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
     * @return \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
    ): \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface {
        try {
            $this->resource->save($storeCreditNotification);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to save store credit notification: %1', $e->getMessage())
            );
        }

        return $storeCreditNotification;
    }

    /**
     * Get a store credit notification by ID.
     *
     * @param int $id
     * @return \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface
    {
        $notification = $this->storeCreditNotificationFactory->create();
        $this->resource->load($notification, $id);

        if (!$notification->getId()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Store Credit Notification with ID "%1" does not exist.', $id)
            );
        }

        return $notification;
    }

    /**
     * Delete a store credit notification.
     *
     * @param \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface $storeCreditNotification
    ): void {
        try {
            $this->resource->delete($storeCreditNotification);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Unable to delete store credit notification: %1', $e->getMessage())
            );
        }
    }

    /**
     * Retrieve store credit notifications by scheduled date and status.
     *
     * @param string $scheduledDate
     * @param string $status
     * @return \Vapewholesaleusa\StoreCreditNotifier\Model\StoreCreditNotification[]
     */
    public function getByScheduledDate(string $scheduledDate, string $status): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('scheduled_date', $scheduledDate);
        $collection->addFieldToFilter('status', $status);

        return $collection->getItems();
    }
}
