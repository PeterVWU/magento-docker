<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface OrderSourceSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface[]
     */
    public function getItems();

    /**
     * @param \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface[] $items
     */
    public function setItems(array $items);
}
