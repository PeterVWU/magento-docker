<?php

declare(strict_types=1);

namespace Vapewholesaleusa\MageWorxOrderEditor\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;

/**
 * Class InvoiceFinder
 * @package Vapewholesaleusa\MageWorxOrderEditor\Model
 */
class InvoiceFinder extends \MageWorx\OrderEditor\Model\InvoiceFinder
{
    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory
     */
    private $itemCollectionFactory;

    /**
     * InvoiceFinder constructor.
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory $itemCollectionFactory
     */
    public function __construct(
        \Magento\Sales\Api\InvoiceRepositoryInterface   $invoiceRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder        $searchCriteriaBuilder,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\CollectionFactory $itemCollectionFactory
    ) {
        parent::__construct($invoiceRepository, $orderItemRepository, $searchCriteriaBuilder);
        $this->invoiceRepository     = $invoiceRepository;
        $this->orderItemRepository   = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->itemCollectionFactory = $itemCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceByOrderItemId(int $orderItemId, ?int $orderId, ?float $qty = 1): array
    {
        if ($orderId === null) {
            $orderItem = $this->orderItemRepository->get($orderItemId);
            $orderId = $orderItem->getOrderId();
        }

        $invoicesList = $this->invoiceRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter('order_id', $orderId)
                ->addFilter('state', [1,2], 'in')
                ->create()
        );

        /** @var \Magento\Sales\Model\Order\Invoice $invoiceItems */
        $invoiceItems = $invoicesList->getItems();
        $processedQty = 0;
        $invoices     = [];
        foreach ($invoiceItems as $invoice) {
            /** @var \Magento\Sales\Model\ResourceModel\Order\Invoice\Item\Collection $itemsCollection */

            $itemsCollection = $this->itemCollectionFactory->create()->setInvoiceFilter($invoice->getId());
            $invoiceItem     = $itemsCollection->getItemByColumnValue('order_item_id', $orderItemId);

            if ($invoiceItem === null) {
                continue; // There are no specified item in that invoice
            }

            $invoices[] = $invoice;
            $qtyLeft    = $qty - $processedQty;
            if ($invoiceItem->getData('qty') >= $qtyLeft) {
                break;
            } else {
                $processedQty += $invoiceItem->getData('qty');
            }
        }

        return $invoices;
    }

}
