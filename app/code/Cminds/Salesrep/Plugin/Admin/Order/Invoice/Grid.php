<?php

declare(strict_types=1);

namespace Cminds\Salesrep\Plugin\Admin\Order\Invoice;

class Grid extends \Magento\Framework\Data\Collection
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $coreResource;

    /**
     * @var \Magento\User\Model\ResourceModel\User\Collection
     */
    protected $adminUsers;

    /**
     * @var \Cminds\Salesrep\Helper\Data
     */
    protected $salesrepHelper;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\ResourceConnection $coreResource
     * @param \Magento\User\Model\ResourceModel\User\Collection $adminUsers
     * @param \Cminds\Salesrep\Helper\Data $salesrepHelper
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Magento\Backend\Model\Auth\Session                       $authSession,
        \Magento\Framework\App\ResourceConnection                 $coreResource,
        \Magento\User\Model\ResourceModel\User\Collection         $adminUsers,
        \Cminds\Salesrep\Helper\Data                              $salesrepHelper
    ) {
        parent::__construct($entityFactory);
        $this->authSession = $authSession;
        $this->coreResource = $coreResource;
        $this->adminUsers = $adminUsers;
        $this->salesrepHelper = $salesrepHelper;
    }

    /**
     * @param $printQuery
     * @param $logQuery
     * @return void
     * @throws \Zend_Db_Select_Exception
     */
    public function beforeLoad($printQuery = false, $logQuery = false)
    {
        $isModuleEnabled = $this->salesrepHelper->isModuleEnabled();

        if ($isModuleEnabled) {
            if ($printQuery instanceof \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult && !($printQuery instanceof \Magento\Sales\Model\ResourceModel\Order\Invoice\Orders\Grid\Collection)) {
                $collection = $printQuery;
                if ($collection->getMainTable() == 'sales_invoice_grid') {
                    $joined_tables = array_keys(
                        $collection->getSelect()->getPart('from')
                    );

                    if (!in_array('salesrep', $joined_tables)) {
                        $order_detail_page = $this->authSession->isAllowed(
                            'Cminds_Salesrep::access_order_detail_page'
                        );
                        $order_detail_page_all = $this->authSession->isAllowed(
                            'Cminds_Salesrep::order_detail_page_access_order_detail_page_all_orders'
                        );
                        $order_detail_page_sub = $this->authSession->isAllowed(
                            'Cminds_Salesrep::order_detail_page_access_order_detail_page_subordinate'
                        );
                        $order_detail_page_own = $this->authSession->isAllowed(
                            'Cminds_Salesrep::order_detail_page_access_order_detail_page_only_own'
                        );

                        $currentUserId = $this->authSession
                            ->getUser()
                            ->getId();
                        $adminUserIds = [];

                        if ($order_detail_page) {
                            $salesrepTable = $this->coreResource
                                ->getTableName("salesrep");

                            $collection->getSelect()
                                ->joinLeft(
                                    ['salesrep' => $salesrepTable],
                                    'salesrep.order_id = main_table.order_id'
                                );

                            $collection->getSelect()->group('main_table.order_id');

                            if (!$order_detail_page_all &&
                                !$order_detail_page_sub &&
                                !$order_detail_page_own) {
                                $collection->clear();
                            }

                            if (!$order_detail_page_all) {
                                if ($order_detail_page_own) {
                                    $adminUserIds = [$currentUserId];
                                }

                                if ($order_detail_page_sub) {
                                    $admin_user_collection = $this->adminUsers;
                                    $admin_user_collection->addFieldToFilter(
                                        'salesrep_manager_id',
                                        $currentUserId
                                    );

                                    foreach ($admin_user_collection as $admin_user) {
                                        $adminUserIds[] = $admin_user->getUserId();
                                    }
                                }

                                $collection->addFieldToFilter(
                                    'salesrep.rep_id',
                                    ['in' => $adminUserIds]
                                );
                            }

                        }
                    }
                }
            }
        }
    }
}
