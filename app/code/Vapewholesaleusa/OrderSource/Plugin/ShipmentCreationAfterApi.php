<?php

namespace Vapewholesaleusa\OrderSource\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\ShipmentDocumentFactory;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Services\GetClientSourceService;

/**
 * Class ShipmentCreationAfterApi
 */
class ShipmentCreationAfterApi
{
    /**
     * @var GetClientSourceService
     */
    private $getClientSourceService;

    /**
     * @var Data
     */
    private $orderSourceHelper;

    /**
     * ShipmentCreationAfterApi constructor.
     * @param GetClientSourceService $getClientSourceService
     * @param Data $orderSourceHelper
     */
    public function __construct(
        GetClientSourceService $getClientSourceService,
        Data $orderSourceHelper
    ) {
        $this->getClientSourceService = $getClientSourceService;
        $this->orderSourceHelper = $orderSourceHelper;
    }

    /**
     * @param ShipmentDocumentFactory $subject
     * @param ShipmentInterface $result
     * @param OrderInterface $order
     * @param ShipmentItemCreationInterface[] $items
     * @param ShipmentTrackCreationInterface[] $tracks
     * @param ShipmentCommentCreationInterface|null $comment
     * @param bool $appendComment
     * @param ShipmentPackageCreationInterface[] $packages
     * @param ShipmentCreationArgumentsInterface|null $arguments
     * @return ShipmentInterface
     */
    public function afterCreate(ShipmentDocumentFactory $subject, ShipmentInterface $result, OrderInterface $order, array $items = [], array $tracks = [], ShipmentCommentCreationInterface $comment = null, $appendComment = false, array $packages = [], ShipmentCreationArgumentsInterface $arguments = null): ShipmentInterface
    {
        if(!$this->orderSourceHelper->isModuleEnabled()) {
            return $result;
        }

        if(!$this->orderSourceHelper->getShippingSourcing()) {
            return $result;
        }

        $source = $this->getClientSourceService->execute();
        if($source) {
            $result->getExtensionAttributes()->setSourceCode($source);
        }

        return $result;
    }
}
