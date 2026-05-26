<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OrderSource extends AbstractDb
{
    public const MAIN_TABLE = 'vapewholesaleusa_ordersource_ordersource';

    public const ID_FIELD_NAME = 'entity_id';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
