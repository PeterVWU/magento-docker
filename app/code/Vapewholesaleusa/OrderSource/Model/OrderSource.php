<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterfaceFactory;

class OrderSource extends AbstractModel
{
    public function __construct(
        Context $context,
        Registry $registry,
        private readonly DataObjectHelper $dataObjectHelper,
        private readonly  OrderSourceInterfaceFactory $orderSourceDataFactory,
        ResourceModel\OrderSource $resource,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct() {

        $this->_init('Vapewholesaleusa\OrderSource\Model\ResourceModel\OrderSource');
    }

    public function getDataModel(): OrderSourceInterface
    {
        $data = $this->getData();

        $dataObject = $this->orderSourceDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $dataObject,
            $data,
            OrderSourceInterfaceFactory::class
        );

        return $dataObject;
    }
}
