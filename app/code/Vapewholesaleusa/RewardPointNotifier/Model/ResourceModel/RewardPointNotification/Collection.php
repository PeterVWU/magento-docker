<?php

declare(strict_types=1);

namespace Vapewholesaleusa\RewardPointNotifier\Model\ResourceModel\RewardPointNotification;

/**
 * Collection for Reward Point Notifications.
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model and resource model.
     */
    protected function _construct(): void
    {
        $this->_init(
            \Vapewholesaleusa\RewardPointNotifier\Model\RewardPointNotification::class,
            \Vapewholesaleusa\RewardPointNotifier\Model\ResourceModel\RewardPointNotification::class
        );
    }
}
