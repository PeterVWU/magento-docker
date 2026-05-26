<?php

namespace Vapewholesaleusa\OrderSource\Queries;

use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Vapewholesaleusa\OrderSource\Api\OrderSourceRepositoryInterface;

class GetOrderTotalItemsBySourceQuery
{
    /**
     * @var OrderSourceRepositoryInterface
     */
    private $orderSourceRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilderFactory
     */
    private $filterBuilder;

    /**
     * @param OrderSourceRepositoryInterface $orderSourceRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilder
     * @param FilterBuilderFactory $filterBuilder
     */
    public function __construct(
        OrderSourceRepositoryInterface $orderSourceRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilder,
        FilterBuilderFactory $filterBuilder
    ) {
        $this->orderSourceRepository = $orderSourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param $orderId
     * @param $source
     * @return float|int|null
     */
    public function execute($orderId, $source) {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->addFilter($this->filterBuilder->create()
            ->setField('order_id')
            ->setValue((int)$orderId)
            ->setConditionType('eq')
            ->create());
        $searchCriteria->addFilter($this->filterBuilder->create()
            ->setField('source_code')
            ->setValue($source)
            ->setConditionType('eq')
            ->create());
        $orderSource = $this->orderSourceRepository->getList($searchCriteria->create())->getItems();
        $itemsTotal = array_reduce($orderSource, function($carry, $item) {
            return $carry + $item->getQty();
        }, 0);
        return $itemsTotal;
    }
}
