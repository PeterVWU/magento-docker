<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vapewholesaleusa\OrderSource\Model\OrderSource;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(OrderSource::class, \Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource::class);
    }
}
