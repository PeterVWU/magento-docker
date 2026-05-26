<?php

namespace Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource\Grid;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource;

/**
 * Order grid collection
 */
class Collection extends SearchResult
{
    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param TimezoneInterface|null $timeZone
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
                      $mainTable = 'vapewholesaleusa_ordersource_ordersource',
                      $resourceModel = OrderSource::class,
        TimezoneInterface $timeZone = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->timeZone = $timeZone ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $tableDescription = $this->getConnection()->describeTable($this->getMainTable());
        foreach ($tableDescription as $columnInfo) {
            $this->addFilterToMap($columnInfo['COLUMN_NAME'], 'main_table.' . $columnInfo['COLUMN_NAME']);
        }

        return $this;
    }
}
