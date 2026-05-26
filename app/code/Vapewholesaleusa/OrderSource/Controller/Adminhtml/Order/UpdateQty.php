<?php

namespace Vapewholesaleusa\OrderSource\Controller\Adminhtml\Order;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Vapewholesaleusa\OrderSource\Services\UpdateQtyOfOrderSource;

/**
 * Class UpdateQty
 */
class UpdateQty extends \Magento\Backend\App\Action
{
    /**
     * @var UpdateQtyOfOrderSource
     */
    private $updateQtyOfOrderSource;

    /**
     * UpdateQty constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param UpdateQtyOfOrderSource $updateQtyOfOrderSource
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        UpdateQtyOfOrderSource $updateQtyOfOrderSource
    ) {
        parent::__construct($context);
        $this->updateQtyOfOrderSource = $updateQtyOfOrderSource;
    }

    /**
     * @return ResponseInterface|Json&ResultInterface
     */
    public function execute()
    {
        $params = $this->_request->getParams();

        if($params['data']){
            try {
                foreach ($params['data'] as $key => $qty){
                    $this->updateQtyOfOrderSource->execute($key, $qty);
                }
                $result = [ 'success' => true, 'message' => 'Qty updated successfully'];
            } catch (\Exception $e) {
                $result = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
