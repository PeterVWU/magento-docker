<?php

use Magento\Framework\App\Bootstrap;
use Magento\Sales\Model\ResourceModel\GridPool;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();
$gridPool = $objectManager->get(GridPool::class);

$orderTable = $resource->getTableName('sales_order');
$gridTable = $resource->getTableName('sales_order_grid');

$orderIds = $connection->fetchCol(
    $connection->select()
        ->from(['orders' => $orderTable], ['entity_id'])
        ->joinLeft(
            ['grid' => $gridTable],
            'grid.entity_id = orders.entity_id',
            []
        )
        ->where('grid.entity_id IS NULL')
        ->order('orders.entity_id ASC')
);

foreach ($orderIds as $orderId) {
    $gridPool->refreshByOrderId((int) $orderId);
}

printf("Refreshed sales grids for %d orders.\n", count($orderIds));
