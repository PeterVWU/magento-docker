<?php

namespace Vapewholesaleusa\OrderSource\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple;
use Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySku;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;

/**
 * Class UpdateQty
 */
class CopyQty extends \Magento\Backend\App\Action
{
    /**
     * @var GetSourceItemsBySku
     */
    private $getSourceItemsBySku;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SaveMultiple
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * private $items
     */
    private $items;

    /**
     * private $count
     */
    private $count;

    /**
     * @param Context $context
     * @param ResourceConnection $resourceConnection
     * @param SaveMultiple $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ResourceConnection $resourceConnection,
        SaveMultiple $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
    ) {
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->items = [];
        $this->count = 0;
    }

    /**
     * @return ResponseInterface|Json&ResultInterface
     */
    public function execute()
    {
        $params = $this->_request->getParams();

        if($params['data']){
            try {
                $sourceCode = $params['data']['source_code'] ?? false;
                $targetCode = $params['data']['target_code'] ?? false;

                if(!$sourceCode || !$targetCode || $sourceCode == $targetCode){
                    throw new \Exception('Source and target code are required, and must be different');
                }

                $allQtys = $this->retrieveAllQtys($sourceCode);
                $step = (int)min(10000, max(count($allQtys) / 20, 1));
                foreach ($allQtys as $stockItem) {
                    $this->updateQtyOfSource($stockItem['sku'], $targetCode, $stockItem['quantity'], $stockItem['status'], $step);
                    $this->count++;
                }

                if(count($this->items) > 0){
                    $this->updateQtyOfSource();
                }

                $result = [ 'success' => true, 'message' => 'Qty updated successfully'];
            } catch (\Exception $e) {
                $result = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * @param $sourceCode
     * @return array
     */
    private function retrieveAllQtys($sourceCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('inventory_source_item');
        $sql = "SELECT sku, quantity, status FROM " . $tableName . " WHERE source_code = '" . $sourceCode . "'";
        $result = $connection->fetchAll($sql);
        return $result;
    }

    /**
     * @param null $sku
     * @param null $sourceCode
     * @param null $quantity
     * @param null $status
     * @param null $step
     * @return void
     * @throws CouldNotSaveException
     */
    private function updateQtyOfSource($sku = null, $sourceCode = null, $quantity = 0, $status = 0, $step = null)
    {
        if($sku && $sourceCode){
            $this->items[] = $this->sourceItemFactory->create(
                [
                    'data' => [
                        'sku' => $sku,
                        'source_code' => $sourceCode,
                        'quantity' => $quantity,
                        'status' => $status
                    ]
                ]
            );
        }


       try {
           if($this->items && (!$step || $this->count >= $step)){
               $this->sourceItemsSave->execute($this->items);
               $this->items = [];
               $this->count = 0;
           }
       } catch (\Exception $e) {
           throw new CouldNotSaveException(__($e->getMessage()));
       }
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
