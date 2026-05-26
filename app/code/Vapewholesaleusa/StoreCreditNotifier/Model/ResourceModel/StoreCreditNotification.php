<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel;

/**
 * Resource model for Store Credit Notifications.
 */
class StoreCreditNotification extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize table and primary key.
     */
    protected function _construct(): void
    {
        $this->_init('store_credit_notifications', 'id');
    }
}
