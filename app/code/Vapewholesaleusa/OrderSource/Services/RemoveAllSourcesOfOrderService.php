<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Vapewholesaleusa\OrderSource\Api\OrderSourceRepositoryInterface;

class RemoveAllSourcesOfOrderService
{
    /**
     * @var OrderSourceRepositoryInterface
     */
    private $orderSourceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * RemoveAllSourcesOfOrderService constructor.
     * @param OrderSourceRepositoryInterface $orderSourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        OrderSourceRepositoryInterface $orderSourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->orderSourceRepository = $orderSourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param int $orderId
     * @return void
     */
    public function execute(int $orderId)
    {
        $filters = $this->filterBuilder->setField('order_id')->setValue($orderId)->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter($filters)->create();

        $orderSources = $this->orderSourceRepository->getList($searchCriteria)->getItems();

        foreach ($orderSources as $orderSource) {
            $this->orderSourceRepository->delete($orderSource);
        }
    }
}
