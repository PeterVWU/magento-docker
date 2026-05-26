<?php

declare(strict_types=1);

namespace Vapewholesaleusa\MageWorxOrderEditor\Model;


class Order extends \MageWorx\OrderEditor\Model\Order
{
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \MageWorx\OrderEditor\Model\Order\SalesProcessorFactory $salesProcessorFactory
     * @param \MageWorx\OrderEditor\Api\QuoteRepositoryInterface $quoteRepository
     * @param \MageWorx\OrderEditor\Model\Invoice $invoice
     * @param \MageWorx\OrderEditor\Model\Shipment $shipment
     * @param \MageWorx\OrderEditor\Model\Creditmemo $creditmemo
     * @param \Magento\Framework\Serialize\Serializer\Json $serializerJson
     * @param \MageWorx\OrderEditor\Api\OrderRepositoryInterface $orderRepository
     * @param \MageWorx\OrderEditor\Api\QuoteItemRepositoryInterface $oeQuoteItemRepository
     * @param \MageWorx\OrderEditor\Api\OrderItemRepositoryInterface $oeOrderItemRepository
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \MageWorx\OrderEditor\Helper\Data $helperData
     * @param \MageWorx\OrderEditor\Model\OrderCollectionFactoryBox $collectionFactoryBox
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Tax\Model\Config $taxConfig,
        \MageWorx\OrderEditor\Model\Order\SalesProcessorFactory $salesProcessorFactory,
        \MageWorx\OrderEditor\Api\QuoteRepositoryInterface $quoteRepository,
        \MageWorx\OrderEditor\Model\Invoice $invoice,
        \MageWorx\OrderEditor\Model\Shipment $shipment,
        \MageWorx\OrderEditor\Model\Creditmemo $creditmemo,
        \Magento\Framework\Serialize\Serializer\Json $serializerJson,
        \MageWorx\OrderEditor\Api\OrderRepositoryInterface $orderRepository,
        \MageWorx\OrderEditor\Api\QuoteItemRepositoryInterface $oeQuoteItemRepository,
        \MageWorx\OrderEditor\Api\OrderItemRepositoryInterface $oeOrderItemRepository,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \MageWorx\OrderEditor\Helper\Data $helperData,
        \MageWorx\OrderEditor\Model\OrderCollectionFactoryBox $collectionFactoryBox,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $timezone,
            $storeManager,
            $orderConfig,
            $productRepository,
            $productVisibility,
            $invoiceManagement,
            $currencyFactory,
            $eavConfig,
            $orderHistoryFactory,
            $priceCurrency,
            $taxConfig,
            $salesProcessorFactory,
            $quoteRepository,
            $invoice,
            $shipment,
            $creditmemo,
            $serializerJson,
            $orderRepository,
            $oeQuoteItemRepository,
            $oeOrderItemRepository,
            $dataObjectFactory,
            $messageManager,
            $helperData,
            $collectionFactoryBox,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function calculateGrandTotal()
    {
        $this->reCalculateTaxAmount();

        // shipping tax
        $tax     = $this->getTaxAmount() + $this->getShippingTaxAmount();
        $baseTax = $this->getBaseTaxAmount() + $this->getBaseShippingTaxAmount();

        $this->setTaxAmount($tax)->setBaseTaxAmount($baseTax);
        $this->orderRepository->save($this);

        // Order GrandTotal include tax
        if ($this->checkTaxConfiguration()) {
            $grandTotal     = $this->getSubtotal()
                + $this->getTaxAmount()
                + $this->getShippingAmount()
                + $this->calculateMageWorxFeeAmount()
                - abs((float)$this->getDiscountAmount())
                - abs((float)$this->getGiftCardsAmount())
                - abs((float)$this->getCustomerBalanceAmount())
                - abs((float)$this->getAmstorecreditAmount());
            $baseGrandTotal = $this->getBaseSubtotal()
                + $this->getBaseTaxAmount()
                + $this->getBaseShippingAmount()
                + $this->calculateMageWorxBaseFeeAmount()
                - abs((float)$this->getBaseDiscountAmount())
                - abs((float)$this->getBaseGiftCardsAmount())
                - abs((float)$this->getBaseCustomerBalanceAmount())
                - abs((float)$this->getAmstorecreditBaseAmount());
        } else {
            $grandTotal     = $this->getSubtotalInclTax()
                + $this->getShippingInclTax()
                + $this->calculateMageWorxFeeAmount()
                - abs((float)$this->getDiscountAmount())
                - abs((float)$this->getGiftCardsAmount())
                - abs((float)$this->getCustomerBalanceAmount())
                - abs((float)$this->getAmstorecreditAmount());
            ;
            $baseGrandTotal = $this->getBaseSubtotalInclTax()
                + $this->getBaseShippingInclTax()
                + $this->calculateMageWorxBaseFeeAmount()
                - abs((float)$this->getBaseDiscountAmount())
                - abs((float)$this->getBaseGiftCardsAmount())
                - abs((float)$this->getBaseCustomerBalanceAmount())
                - abs((float)$this->getAmstorecreditBaseAmount());
            ;
        }

        if ((float)$this->getGrandTotal() != (float)$grandTotal) {
            $this->_eventManager->dispatch(
                'mageworx_log_changes_on_order_edit',
                [
                    \MageWorx\OrderEditor\Api\ChangeLoggerInterface::SIMPLE_MESSAGE_KEY => __(
                        '<b>Grand Total</b> has been changed from <b>%1</b> to <b>%2</b>',
                        $this->formatPriceTxt($this->getGrandTotal()),
                        $this->formatPriceTxt($grandTotal)
                    )
                ]
            );
        }

        $this->setGrandTotal($grandTotal)
            ->setBaseGrandTotal($baseGrandTotal);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function updateOrderItems()
    {
        foreach ($this->newParams as $id => $params) {
            if (!$this->isItemChangedDirectly($params)) {
                continue;
            }
            if (!empty($params['item_type']) && $params['item_type'] === 'quote') {
                $id = null;
            }

            if (!$id && !empty($params['parent'])) {
                continue;
            }

            try {
                $item = $this->loadOrderItem($id, $params);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $noSuchEntityException) {
                continue;
            }

            // Here we're changing item qty, removing or adding items
            /* var $item \MageWorx\OrderEditor\Model\Order\Item */
            $orderItem = $item->editItem($params, $this);

            $this->collectItemsChanges($orderItem);

            $this->oeOrderItemRepository->save($orderItem);
        }
    }

    /**
     * @param $params
     * @return bool
     */
    protected function isItemChangedDirectly($params)
    {
        if (isset($params['item_type']) && $params['item_type'] === 'quote') {
            return true;
        }
        if (isset($params['action']) && $params['action'] === 'remove') {
            return true;
        }
        $fieldsToCheck = ['fact_qty', 'price', 'subtotal', 'tax_amount', 'discount_amount'];

        foreach ($fieldsToCheck as $field) {

            if (isset($params['orig_' . $field], $params[$field]) && $params['orig_' . $field] != $params[$field]) {
                return true;
            }

            if (in_array($field, ['discount_amount', 'tax_amount']) && isset($params[$field]) && $params[$field] > 0) {
                return true;
            }
        }
        return false;
    }
}
