<?php

namespace Vapewholesaleusa\OrderSource\Queries;

use Magento\Framework\App\ResourceConnection;
use Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource;

class GetAllOrderSourcesQuery
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
     * @param $orderId
     * @return array
     */
    public function execute($orderId)
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceItemTable = $this->resourceConnection->getTableName(OrderSource::MAIN_TABLE);
        $sql = "SELECT * FROM $sourceItemTable WHERE order_id = :order_id";
        $bind = [
            'order_id' => $orderId
        ];
        $result = $connection->fetchAll($sql, $bind);
        return $result;
    }
}
