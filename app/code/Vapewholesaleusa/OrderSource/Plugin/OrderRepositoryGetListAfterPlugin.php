<?php

namespace Vapewholesaleusa\OrderSource\Plugin;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\OrderRepository;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Queries\GetOrderTotalItemsBySourceQuery;
use Vapewholesaleusa\OrderSource\Queries\GetQtyForOrderItemSourceQuery;
use Vapewholesaleusa\OrderSource\Services\CheckFallBackService;
use Vapewholesaleusa\OrderSource\Services\GetClientSourceService;

/**
 * Class OrderRepositoryGetListAfterPlugin
 */
class OrderRepositoryGetListAfterPlugin
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
     * @param OrderSearchResultInterface $result
     * @param SearchCriteriaInterface $searchCriteria
     * @return OrderSearchResultInterface
     */
    public function afterGetList(OrderRepository $subject, OrderSearchResultInterface $result, SearchCriteriaInterface $searchCriteria): OrderSearchResultInterface
    {
        if(!$this->orderSourceHelper->isModuleEnabled()) {
            return $result;
        }

        if(!$this->orderSourceHelper->getOrderSourcing()) {
            return $result;
        }

        $source = $this->getClientSourceService->execute();
        if($source && $this->request->getMethod() == 'GET') {
            foreach ($result->getItems() as $order) {

                if($this->checkFallBackService->execute($order->getEntityId(), $source)) {
                    continue;
                }

                $totalQtyOrdered = $this->getOrderTotalItemsBySourceQuery->execute($order->getEntityId(), $source);
                $order->setTotalItemCount($totalQtyOrdered);
                $order->setTotalQtyOrdered($totalQtyOrdered);
                foreach ($order->getItems() as $item) {
                    $item->setSourceCode($source);
                    $orderSource = $this->getQtyForOrderItemSourceService->execute($order->getEntityId(), $item->getSku(), $source);
                    $item->setQtyOrdered($orderSource?->getQty() ?? 0);
                    $item->setQtyShipped($orderSource?->getQtyShipped() ?? 0);
                }
            }
        }

        return $result;
    }
}
