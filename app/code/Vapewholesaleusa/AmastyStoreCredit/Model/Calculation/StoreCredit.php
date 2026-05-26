<?php

declare(strict_types=1);

namespace Vapewholesaleusa\AmastyStoreCredit\Model\Calculation;

/**
 * Class StoreCredit
 * @package Vapewholesaleusa\AmastyStoreCredit\Model\Calculation
 */
class StoreCredit extends \Amasty\StoreCredit\Model\Calculation\StoreCredit
{
    /**
     * @var \Amasty\StoreCredit\Model\Calculation\StoreCredit\Applier
     */
    private $applier;

    /**
     * @var \Amasty\StoreCredit\Model\Total\Quote\FilteredItems
     */
    private $filteredItems;

    /**
     * @var \Amasty\StoreCredit\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\StoreCredit\Model\Calculation\Distributor
     */
    private $distributor;

    /**
     * @var \Amasty\StoreCredit\Model\Calculation\Currency
     */
    private $currency;

    /**
     * @var \Amasty\StoreCredit\Model\Calculation\ItemAmountCalculator
     */
    private $itemAmountCalculator;

    /**
     * StoreCredit constructor.
     * @param \Amasty\StoreCredit\Model\Calculation\StoreCredit\Applier $applier
     * @param \Amasty\StoreCredit\Model\Total\Quote\FilteredItems $filteredItems
     * @param \Amasty\StoreCredit\Model\ConfigProvider $configProvider
     * @param \Amasty\StoreCredit\Model\Calculation\Distributor $distributor
     * @param \Amasty\StoreCredit\Model\Calculation\Currency $currency
     * @param \Amasty\StoreCredit\Model\Calculation\ItemAmountCalculator $itemAmountCalculator
     */
    public function __construct(
        \Amasty\StoreCredit\Model\Calculation\StoreCredit\Applier $applier,
        \Amasty\StoreCredit\Model\Total\Quote\FilteredItems $filteredItems,
        \Amasty\StoreCredit\Model\ConfigProvider $configProvider,
        \Amasty\StoreCredit\Model\Calculation\Distributor $distributor,
        \Amasty\StoreCredit\Model\Calculation\Currency $currency,
        \Amasty\StoreCredit\Model\Calculation\ItemAmountCalculator $itemAmountCalculator
    ) {
        parent::__construct(
            $applier,
            $filteredItems,
            $configProvider,
            $distributor,
            $currency,
            $itemAmountCalculator
        );
        $this->applier = $applier;
        $this->filteredItems = $filteredItems;
        $this->configProvider = $configProvider;
        $this->distributor = $distributor;
        $this->currency = $currency;
        $this->itemAmountCalculator = $itemAmountCalculator;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param float $creditAmount
     * @param float $shippingAmount
     */
    public function splitStoreCreditByItemsAndShipping(
        \Magento\Quote\Model\Quote $quote,
        float $creditAmount,
        float $shippingAmount
    ): void {
        if (!$creditAmount) {
            return;
        }

        $currencyCode = $quote->getQuoteCurrencyCode();
        $baseCreditAmount = $creditAmount / $this->currency->getCurrencyRate($currencyCode, $quote->getStoreId());

        if ($this->configProvider->isAllowOnShipping($quote->getStoreId())) {
            if ($baseCreditAmount <= $shippingAmount) {
                $this->applier->applyShippingToQuote($quote, $baseCreditAmount);

                return;
            }
            $baseCreditAmount -= $shippingAmount;
            $this->applier->applyShippingToQuote($quote, $shippingAmount);
        }

        $items = $this->filteredItems->getFilteredItems();
        if (!$items) {
            return;
        }

        usort($items, [$this, 'sortItems']);

        $allCartPrice = $this->itemAmountCalculator->getAllItemsPrice($items);
        if ($allCartPrice == 0) {
            return;
        }
        $percent = ($baseCreditAmount * 100) / $allCartPrice;
        $itemsStoreCredit = $this->distributor->distribute($items, $baseCreditAmount, $percent);

        $this->applier->applyToQuoteItems($items, $itemsStoreCredit);
    }

    /**
     * Sorting items before apply reward points
     * cheapest should go first
     *
     * @param \Magento\Quote\Model\Quote\Item $itemA
     * @param \Magento\Quote\Model\Quote\Item $itemB
     *
     * @return int
     */
    private function sortItems(\Magento\Quote\Model\Quote\Item $itemA, \Magento\Quote\Model\Quote\Item $itemB): int
    {
        if ($this->itemAmountCalculator->calculateItemAmount($itemA)
            > $this->itemAmountCalculator->calculateItemAmount($itemB)
        ) {
            return 1;
        }

        if ($this->itemAmountCalculator->calculateItemAmount($itemA)
            < $this->itemAmountCalculator->calculateItemAmount($itemB)
        ) {
            return -1;
        }

        return 0;
    }
}
