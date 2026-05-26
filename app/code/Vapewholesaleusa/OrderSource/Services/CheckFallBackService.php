<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Queries\GetAllOrderSourcesQuery;

class CheckFallBackService
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var GetAllOrderSourcesQuery
     */
    private $getAllOrderSourcesQuery;

    /**
     * CheckFallBackService constructor.
     * @param Data $helper
     * @param GetAllOrderSourcesQuery $getAllOrderSourcesQuery
     */
    public function __construct(
        Data $helper,
        GetAllOrderSourcesQuery $getAllOrderSourcesQuery
    ) {
        $this->helper = $helper;
        $this->getAllOrderSourcesQuery = $getAllOrderSourcesQuery;
    }

    /**
     * @param $orderId
     * @param $sourceCode
     * @return bool
     */
    public function execute($orderId, $sourceCode)
    {
        $fallBackSource = $this->helper->getFallbackSource();
        if(!$fallBackSource) {
            return false;
        }

        $orderSources = $this->getAllOrderSourcesQuery->execute($orderId);
        if(count($orderSources) == 0) {
            if($fallBackSource == $sourceCode) {
                return true;
            }
        }

        return false;
    }
}
