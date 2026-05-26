<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Magento\Framework\Api\FilterBuilderFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Vapewholesaleusa\OrderSource\Api\OrderSourceRepositoryInterface;

/**
 * Class AddShipmentService
 */
class AddShipmentService
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
     * @param $qty
     * @return void
     */
    public function execute($orderId, $sku, $source, $qty)
    {
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
        $qty = $qty ?? 0;
        if(count($orderSource) > 0) {
            $orderSource = array_shift($orderSource);
            $qty = $orderSource->getQtyShipped() + $qty;
            $orderSource->setQtyShipped($qty);
            if($qty >= $orderSource->getQty()) {
                $orderSource->setStatus(1);
            }
            $this->orderSourceRepository->save($orderSource);
        }
    }
}
