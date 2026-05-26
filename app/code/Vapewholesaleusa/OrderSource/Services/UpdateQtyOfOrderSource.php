<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Vapewholesaleusa\OrderSource\Model\OrderSourceFactory;

class UpdateQtyOfOrderSource
{
    /**
     * @var OrderSourceFactory
     */
    private $orderSourceFactory;

    /**
     * UpdateQtyOfOrderSource constructor.
     * @param OrderSourceFactory $orderSourceFactory
     */
    public function __construct(
        OrderSourceFactory $orderSourceFactory
    ) {
        $this->orderSourceFactory = $orderSourceFactory;
    }

    /**
     * @param $entityId
     * @param $qty
     * @return bool
     * @throws \Exception
     */
    public function execute($entityId, $qty): bool
    {
        try {
            $orderSource = $this->orderSourceFactory->create()->load($entityId);
            $qty = max(0, $qty);
            $orderSource->setQty($qty);

            if($qty > $orderSource->getQtyShipped() && $orderSource->getStatus() == 1) {
                $orderSource->setStatus(0);
            }

            $orderSource->save();
            if($orderSource->getId()) {
                return true;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return false;
    }
}
