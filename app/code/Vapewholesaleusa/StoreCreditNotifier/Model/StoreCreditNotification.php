<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Model;

/**
 * Model for managing store credit notifications.
 */
class StoreCreditNotification extends \Magento\Framework\Model\AbstractModel implements \Vapewholesaleusa\StoreCreditNotifier\Api\Data\StoreCreditNotificationInterface
{
    /**
     * Define resource model.
     */
    protected function _construct(): void
    {
        $this->_init(\Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel\StoreCreditNotification::class);
    }

    /**
     * Get serialized customer data.
     *
     * @return string|null
     */
    public function getDataSerialized(): ?string
    {
        return $this->getData('data');
    }

    /**
     * Set serialized customer data.
     *
     * @param string $data
     * @return self
     */
    public function setDataSerialized(string $data): self
    {
        return $this->setData('data', $data);
    }

    /**
     * Get scheduled date.
     *
     * @return string|null
     */
    public function getScheduledDate(): ?string
    {
        return $this->getData('scheduled_date');
    }

    /**
     * Set scheduled date.
     *
     * @param string $scheduledDate
     * @return self
     */
    public function setScheduledDate(string $scheduledDate): self
    {
        return $this->setData('scheduled_date', $scheduledDate);
    }

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getData('status');
    }

    /**
     * Set status.
     *
     * @param string $status
     * @return self
     */
    public function setStatus(string $status): self
    {
        return $this->setData('status', $status);
    }
}
