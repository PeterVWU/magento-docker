<?php

namespace Vapewholesaleusa\OrderSource\Plugin;

use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Queries\GetOrderTotalItemsBySourceQuery;
use Vapewholesaleusa\OrderSource\Queries\GetQtyForOrderItemSourceQuery;
use Vapewholesaleusa\OrderSource\Queries\GetQtyForOrderItemSourceService;
use Vapewholesaleusa\OrderSource\Services\CheckFallBackService;
use Vapewholesaleusa\OrderSource\Services\GetClientSourceService;

/**
 * Class OrderRepositoryGetAfterPlugin
 */
class OrderRepositoryGetAfterPlugin
{
    /**
     * @var GetClientSourceService
     */
    private $getClientSourceService;

    /**
     * @var GetQtyForOrderItemSourceQuery
     */
    private $getQtyForOrderItemSourceService;

    /**
     * @var GetOrderTotalItemsBySourceQuery
     */
    private $getOrderTotalItemsBySourceQuery;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var Data
     */
    private $orderSourceHelper;

    /**
     * @var CheckFallBackService
     */
    private $checkFallBackService;

    /**
     * OrderRepositoryGetAfterPlugin constructor.
     * @param GetClientSourceService $getClientSourceService
     * @param GetQtyForOrderItemSourceQuery $getQtyForOrderItemSourceService
     * @param GetOrderTotalItemsBySourceQuery $getOrderTotalItemsBySourceQuery
     * @param Http $request
     * @param Data $orderSourceHelper
     */
    public function __construct(
        GetClientSourceService $getClientSourceService,
        GetQtyForOrderItemSourceQuery $getQtyForOrderItemSourceService,
        GetOrderTotalItemsBySourceQuery $getOrderTotalItemsBySourceQuery,
        Http $request,
        Data $orderSourceHelper,
        CheckFallBackService $checkFallBackService
    ) {
        $this->getClientSourceService = $getClientSourceService;
        $this->getQtyForOrderItemSourceService = $getQtyForOrderItemSourceService;
        $this->getOrderTotalItemsBySourceQuery = $getOrderTotalItemsBySourceQuery;
        $this->request = $request;
        $this->orderSourceHelper = $orderSourceHelper;
        $this->checkFallBackService = $checkFallBackService;
    }

    /**
     * @param OrderRepository $subject
     * @param OrderInterface $result
     * @param int $id
     * @return OrderInterface
     */
    public function afterGet(OrderRepository $subject, OrderInterface $result, $id): OrderInterface
    {
        if(!$this->orderSourceHelper->isModuleEnabled()) {
            return $result;
        }

        if(!$this->orderSourceHelper->getOrderSourcing()) {
            return $result;
        }

        $source = $this->getClientSourceService->execute();
        if($source && $this->request->getMethod() == 'GET') {

            if($this->checkFallBackService->execute($result->getEntityId(), $source)) {
                return $result;
            }

            $totalQtyOrdered = $this->getOrderTotalItemsBySourceQuery->execute($result->getEntityId(), $source);
            $result->setTotalItemCount($totalQtyOrdered);
            $result->setTotalQtyOrdered($totalQtyOrdered);
            foreach ($result->getItems() as $item) {
                $item->setSourceCode($source);
                $orderSource = $this->getQtyForOrderItemSourceService->execute($result->getEntityId(), $item->getSku(), $source);
                $item->setQtyOrdered($orderSource?->getQty() ?? 0);
                $item->setQtyShipped($orderSource?->getQtyShipped() ?? 0);
            }
        }

        return $result;
    }
}
