<?php

namespace Vapewholesaleusa\OrderSource\Plugin;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\OrderRepository;
use Vapewholesaleusa\OrderSource\Model\Helper\Data as OrderSourceHelper;
use Vapewholesaleusa\OrderSource\Services\GetClientSourceService;

/**
 * Class OrderRepositoryGetListAfterPlugin
 */
class OrderGetListBeforePlugin
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var GetClientSourceService
     */
    private $getClientSourceService;

    /**
     * @var OrderSourceHelper
     */
    private $orderSourceHelper;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * OrderRepositoryGetListAfterPlugin constructor.
     * @param Http $request
     * @param GetClientSourceService $getClientSourceService
     * @param OrderSourceHelper $orderSourceHelper
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        Http $request,
        GetClientSourceService $getClientSourceService,
        OrderSourceHelper $orderSourceHelper,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->request = $request;
        $this->getClientSourceService = $getClientSourceService;
        $this->orderSourceHelper = $orderSourceHelper;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * @param OrderRepository $subject
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function beforeGetList(OrderRepository $subject, SearchCriteriaInterface $searchCriteria): array
    {
        if(!$this->orderSourceHelper->isModuleEnabled()) {
            return [$searchCriteria];
        }

        $maxOrder = $this->orderSourceHelper->getMaxOrder();
        $source = $this->getClientSourceService->execute();

        if($source && $maxOrder > 0 && $this->request->getMethod() == 'GET') {
            $searchCriteria->setPageSize($maxOrder);
            $sortOrder = $this->sortOrderBuilder->setField('entity_id')->setDirection('DESC')->create();
            $searchCriteria->setSortOrders([$sortOrder]);
        }

        return [$searchCriteria];
    }
}
