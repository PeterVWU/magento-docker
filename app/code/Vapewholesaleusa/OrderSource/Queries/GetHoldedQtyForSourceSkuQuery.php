<?php

namespace Vapewholesaleusa\OrderSource\Queries;

use Magento\Framework\App\ResourceConnection;
use Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource;

class GetHoldedQtyForSourceSkuQuery
{
    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $sku
     * @param $source
     * @return int
     */
    public function execute($sku, $source)
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceItemTable = $this->resourceConnection->getTableName(OrderSource::MAIN_TABLE);
        $sql = "SELECT SUM(qty) as qty, SUM(qty_shipped) as shipped FROM $sourceItemTable WHERE sku = :sku AND source_code = :source_code AND status = 0";
        $bind = [
            'sku' => $sku,
            'source_code' => $source
        ];
        $result = $connection->fetchRow($sql, $bind);
        $qty = $result ? max(($result['qty'] - $result['shipped']), 0) : 0;
        return (int)$qty;
    }

}
