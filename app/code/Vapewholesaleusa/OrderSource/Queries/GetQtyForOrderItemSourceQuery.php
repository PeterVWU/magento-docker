<?php

namespace Vapewholesaleusa\OrderSource\Queries;

use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;
use Vapewholesaleusa\OrderSource\Api\OrderSourceRepositoryInterface;
use function Vapewholesaleusa\OrderSource\Services\count;

/**
 * Class GetQtyForOrderItemSourceService
 */
class GetQtyForOrderItemSourceQuery
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
     * @param $sku
     * @param $source
     * @return OrderSourceInterface|null
     */
    public function execute($orderId, $sku, $source): ?OrderSourceInterface {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->addFilter($this->filterBuilder->create()
            ->setField('order_id')
            ->setValue((int)$orderId)
            ->setConditionType('eq')
            ->create());
        $searchCriteria->addFilter($this->filterBuilder->create()
                ->setField('sku')
                ->setValue($sku)
                ->setConditionType('eq')
                ->create());
        $searchCriteria->addFilter($this->filterBuilder->create()
                ->setField('source_code')
                ->setValue($source)
                ->setConditionType('eq')
                ->create());
        $orderSource = $this->orderSourceRepository->getList($searchCriteria->create())->getItems();
        if (isset($orderSource[0])) {
            return $orderSource[0];
        }
        return null;
    }
}
