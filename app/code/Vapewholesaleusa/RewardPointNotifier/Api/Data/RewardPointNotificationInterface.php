<?php

declare(strict_types=1);

namespace Vapewholesaleusa\RewardPointNotifier\Api\Data;

/**
 * Interface for Reward Point Notification.
 */
interface RewardPointNotificationInterface
{
    /**
     * Get serialized customer data.
     *
     * @return string|null
     */
    public function getDataSerialized(): ?string;

    /**
     * Set serialized customer data.
     *
     * @param string $data
     * @return self
     */
    public function setDataSerialized(string $data): self;

    /**
     * Get scheduled date.
     *
     * @return string|null
     */
    public function getScheduledDate(): ?string;

    /**
     * Set scheduled date.
     *
     * @param string $scheduledDate
     * @return self
     */
    public function setScheduledDate(string $scheduledDate): self;

    /**
     * Get status.
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status.
     *
     * @param string $status
     * @return self
     */
    public function setStatus(string $status): self;
}
