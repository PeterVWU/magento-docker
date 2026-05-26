<?php

declare(strict_types=1);

namespace Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel\StoreCreditNotification;

/**
 * Collection for Store Credit Notifications.
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model and resource model.
     */
    protected function _construct(): void
    {
        $this->_init(
            \Vapewholesaleusa\StoreCreditNotifier\Model\StoreCreditNotification::class,
            \Vapewholesaleusa\StoreCreditNotifier\Model\ResourceModel\StoreCreditNotification::class
        );
    }
}
