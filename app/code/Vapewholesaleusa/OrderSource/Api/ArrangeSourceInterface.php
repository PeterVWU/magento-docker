<?php

namespace Vapewholesaleusa\OrderSource\Api;

interface ArrangeSourceInterface
{
    /**
     * @param $orderId
     * @param $sku
     * @param $qty
     * @return array
     */
    public function execute($orderId, $sku, $qty): array;
}
