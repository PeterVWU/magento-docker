<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CmindsSalesrep\Plugin\Repository;

class Order
{
    /**
     * Order constructor.
     *
     * @param \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterfaceFactory $salesDataFactory
     * @param \Magento\Sales\Api\Data\OrderExtensionInterfaceFactory $orderExtensionFactory
     * @param \Cminds\Salesrep\Model\ResourceModel\SalesrepRepository $salesrepRepository
     * @param \Magento\Framework\App\ResourceConnection $connection
     */
    public function __construct(
        private \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterfaceFactory $salesDataFactory,
        private \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory,
        private readonly \Cminds\Salesrep\Model\ResourceModel\SalesrepRepository $salesrepRepository,
        private readonly \Magento\Framework\App\ResourceConnection $connection
    ) {
    }

    /**
     *  Plugin for OrderRepository to add sales representative data to orders
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $resultOrder
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function afterGetList(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Model\ResourceModel\Order\Collection $resultOrder
    ): \Magento\Sales\Model\ResourceModel\Order\Collection {
        foreach ($resultOrder->getItems() as $order) {
            /** @var \Magento\Sales\Api\Data\OrderInterface $order */
            $this->addSalesRep($order);
        }
        return $resultOrder;
    }

    /**
     * Attaches sales representative data to order extension attributes
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function addSalesRep(\Magento\Sales\Api\Data\OrderInterface $order): \Magento\Sales\Api\Data\OrderInterface
    {
        $extensionAttributes = $order->getExtensionAttributes();

        /** @var \Cminds\Salesrep\Model\Salesrep $salesRep */
        $salesRep = $this->salesrepRepository->getByOrderId($order->getEntityId());
        if (!$salesRep->getId()) {
            return $order;
        }

        /** @var \Magento\Sales\Api\Data\OrderExtensionInterface $orderExtension */
        $orderExtension = $extensionAttributes ?: $this->orderExtensionFactory->create();

        /** @var \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface $salesData */
        $salesData = $this->salesDataFactory->create();

        $salesData->setSalesrepId($salesRep->getId())
            ->setOrderId($order->getEntityId())
            ->setRepId($salesRep->getRepId())
            ->setRepName($salesRep->getRepName())
            ->setRepEmail((string)$this->loadUserEmailById($salesRep->getRepId()))
            ->setRepCommissionEarned($salesRep->getRepCommissionEarned())
            ->setRepCommissionStatus($salesRep->getRepCommissionStatus())
            ->setManagerId($salesRep->getManagerId())
            ->setManagerName($salesRep->getManagerName())
            ->setManagerCommissionEarned($salesRep->getManagerCommissionEarned())
            ->setManagerCommissionStatus($salesRep->getManagerCommissionStatus())
            ->setCoordinatorCommissionEarned($salesRep->getManagerCommissionStatus())
            ->setCoordinatorCommissionStatus($salesRep->getCoordinatorCommissionStatus());

        $orderExtension->setCmindsSales([$salesData]);

        $order->setExtensionAttributes($orderExtension);

        $order->setExtensionAttributes($orderExtension);

        return $order;
    }

    /**
     * Get admin user by id
     *
     * @param string|null $userId
     * @return mixed
     */
    public function loadUserEmailById(?string $userId): mixed
    {
        $connection = $this->connection->getConnection();

        $select = $connection->select()->from('admin_user', ['email'])->where('user_id=:userId');

        $binds = ['userId' => $userId];

        return $connection->fetchOne($select, $binds);
    }
}
