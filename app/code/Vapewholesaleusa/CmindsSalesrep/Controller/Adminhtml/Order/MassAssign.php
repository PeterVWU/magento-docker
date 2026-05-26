<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CmindsSalesrep\Controller\Adminhtml\Order;

class MassAssign extends \Magento\Backend\App\Action
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Cminds\Salesrep\Api\SalesrepRepositoryInterface $salesrepRepositoryInterface
     * @param \Magento\User\Model\ResourceModel\User\Collection $adminUsers
     */
    public function __construct(
        \Magento\Backend\App\Action\Context                                         $context,
        private readonly \Magento\Ui\Component\MassAction\Filter                    $filter,
        private readonly \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        private readonly \Cminds\Salesrep\Api\SalesrepRepositoryInterface           $salesrepRepositoryInterface,
        private readonly \Magento\User\Model\ResourceModel\User\Collection          $adminUsers
    ) {
        parent::__construct($context);
    }

    /**
     * Executes mass action for assigning a sales representative to orders.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute(): \Magento\Backend\Model\View\Result\Redirect
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $this->massAction($collection);
            $this->messageManager->addSuccessMessage(
                __('Sales representative has been successfully assigned to the order(s)')
            );
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('sales/order');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('sales/order');
        }
    }

    /**
     * Assigns sales representatives, managers, and coordinators to orders.
     *
     * @param $collection
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function massAction($collection): void
    {
        $params = $this->getRequest()->getParams();
        foreach ($collection as $item) {
            $salesrep = $this->salesrepRepositoryInterface
                ->getByOrderId($item->getId());

            if ($params['salesrep_id']) {
                $adminUser = $this->adminUsers
                    ->getItemById($params['salesrep_id']);
            } else {
                $adminUser = false;
            }

            if (!$salesrep->getId()) {
                $salesrep
                    ->setOrderId($item->getId())
                    ->setRepId($params['salesrep_id']);
            }

            if ($adminUser) {
                // Salesrep
                $salesrep
                    ->setRepId($adminUser->getId())
                    ->setRepName(
                        $adminUser->getData('firstname') . ' ' .
                        $adminUser->getData('lastname')
                    );

                $repCommissionEarned = $this->salesrepRepositoryInterface
                    ->getRepCommissionEarned(
                        $item->getId(),
                        $adminUser->getData('salesrep_rep_commission_rate')
                    );

                $salesrep
                    ->setRepCommisionEarned($repCommissionEarned);

                // Manager
                $managerId = $adminUser->getData('salesrep_manager_id');
                if ($managerId && $managerId != 0) {
                    $salesrepManager = $this->adminUsers->getItemById($managerId);
                    if ($salesrepManager && $salesrepManager->getId()) {
                        if ($repCommissionEarned) {
                            if ($salesrepManager->getSalesrepRepCommissionCalculationType() ==
                                \Cminds\Salesrep\Model\Source\CalculationType::MARGIN_CALCULATION_TYPE
                            ) {
                                $managerCommissionEarned = $this->salesrepRepositoryInterface
                                    ->getManagementCommissionEarned(
                                        $item->getId(),
                                        $salesrepManager->getSalesrepManagerCommissionRate(),
                                        $salesrepManager->getSalesrepRepCommissionCalculationType()
                                    );
                            } else {
                                $managerCommissionEarned = $this->salesrepRepositoryInterface
                                    ->getManagerCommissionEarned(
                                        $item->getId(),
                                        $salesrepManager->getSalesrepManagerCommissionRate(),
                                        floatval($repCommissionEarned)
                                    );
                            }
                        }

                        $salesrep
                            ->setManagerId($managerId)
                            ->setManagerName(
                                $salesrepManager->getData('firstname') . ' ' .
                                $salesrepManager->getData('lastname')
                            );
                        if (isset($managerCommissionEarned)) {
                            $salesrep
                                ->setManagerCommissionEarned(
                                    $managerCommissionEarned
                                );
                        } else {
                            $salesrep->setManagerCommissionEarned(0);
                        }
                    }
                } else {
                    $salesrep
                        ->setManagerId(null)
                        ->setManagerName(null)
                        ->setManagerCommissionEarned(0);
                }

                // Coordinator
                $coordinatorId = $adminUser->getData('salesrep_coordinator_id');
                if ($coordinatorId && $coordinatorId != 0) {
                    $salesrepCoordinator = $this->adminUsers->getItemById($coordinatorId);
                    if ($salesrepCoordinator && $salesrepCoordinator->getId()) {
                        if ($repCommissionEarned) {

                            if ($salesrepCoordinator->getSalesrepRepCommissionCalculationType() ==
                                \Cminds\Salesrep\Model\Source\CalculationType::MARGIN_CALCULATION_TYPE
                            ) {
                                $coordinatorCommissionEarned = $this->salesrepRepositoryInterface
                                    ->getManagementCommissionEarned(
                                        $item->getId(),
                                        $salesrepCoordinator->getSalesrepCoordinatorCommissionRate(),
                                        $salesrepCoordinator->getSalesrepRepCommissionCalculationType()
                                    );
                            } else {
                                $coordinatorCommissionEarned = $this->salesrepRepositoryInterface
                                    ->getManagerCommissionEarned(
                                        $item->getId(),
                                        $salesrepCoordinator->getSalesrepCoordinatorCommissionRate(),
                                        floatval($repCommissionEarned)
                                    );
                            }
                        }

                        $salesrep
                            ->setCoordinatorId($coordinatorId)
                            ->setCoordinatorName(
                                $salesrepCoordinator->getData('firstname') . ' ' .
                                $salesrepCoordinator->getData('lastname')
                            );
                        if (isset($coordinatorCommissionEarned)) {
                            $salesrep
                                ->setCoordinatorCommissionEarned(
                                    $coordinatorCommissionEarned
                                );
                        } else {
                            $salesrep->setCoordinatorCommissionEarned(0);
                        }
                    }
                } else {
                    $salesrep
                        ->setCoordinatorId(null)
                        ->setCoordinatorName(null)
                        ->setCoordinatorCommissionEarned(0);
                }

                if (!$salesrep->getSalesrepId()) {
                    $salesrep->setOrderId($item->getId());
                }
            } else {
                $salesrep
                    ->setRepId(null)
                    ->setRepName(null)
                    ->setRepCommisionEarned(0)
                    ->setManagerId(null)
                    ->setManagerName(null)
                    ->setManagerCommissionEarned(0)
                    ->setCoordinatorId(null)
                    ->setCoordinatorName(null)
                    ->setCoordinatorCommissionEarned(0);
            }

            $this->salesrepRepositoryInterface->save($salesrep);
        }
    }
}
