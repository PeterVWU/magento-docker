<?php

namespace Vapewholesaleusa\OrderSource\Cron;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource;

/**
 * Class CleanUp
 */
class CleanUp
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Data
     */
    private $orderSourceHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CleanUp constructor.
     * @param ResourceConnection $resourceConnection
     * @param Data $orderSourceHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Data $orderSourceHelper,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->orderSourceHelper = $orderSourceHelper;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute()
    {
       $period = $this->orderSourceHelper->getCleanUpPeriod();
       if(!$period || $period == 'off'){
           return;
       }
       try {
           $date = $this->convertPeriodToDate($period);
           $connection = $this->resourceConnection->getConnection();
           $sourceItemTable = $this->resourceConnection->getTableName(OrderSource::MAIN_TABLE);
           $sql = "DELETE FROM $sourceItemTable WHERE updated_at < :date AND status = 1";
           $bind = [
               'date' => $date
           ];
           $connection->query($sql, $bind);
           $this->logger->info('Order Source Cleanup Cron Job executed successfully');
       } catch (\Exception $e) {
           $this->logger->error($e->getMessage());
       }
    }

    /**
     * @param $period
     * @return string
     */
    private function convertPeriodToDate($period)
    {
        $date = new \DateTime();
        $date->modify('-' . $period . ' day');
        return $date->format('Y-m-d H:i:s');
    }
}
