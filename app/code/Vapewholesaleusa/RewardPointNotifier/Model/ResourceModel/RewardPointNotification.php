<?php

declare(strict_types=1);

namespace Vapewholesaleusa\RewardPointNotifier\Model\ResourceModel;

/**
 * Resource model for Reward Point Notifications.
 */
class RewardPointNotification extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize table and primary key.
     */
    protected function _construct(): void
    {
        $this->_init('reward_point_notifications', 'id');
    }
}
