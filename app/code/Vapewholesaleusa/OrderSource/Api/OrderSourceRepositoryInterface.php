<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

interface OrderSourceRepositoryInterface
{
    /**
     * @param int $id
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function get(int $id): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
      * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
      * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceSearchResultsInterface
      */
    public function getList(SearchCriteriaInterface $criteria): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceSearchResultsInterface;

    /**
     * @param \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface $entity
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function save(OrderSourceInterface $entity): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
      * @param \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface $entity
      * @return bool
      */
    public function delete(OrderSourceInterface $entity): bool;

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool;
}
