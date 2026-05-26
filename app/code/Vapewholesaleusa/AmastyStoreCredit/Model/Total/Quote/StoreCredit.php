<?php

declare(strict_types=1);

namespace Vapewholesaleusa\AmastyStoreCredit\Model\Total\Quote;

/**
 * Class StoreCredit
 * @package Vapewholesaleusa\AmastyStoreCredit\Model\Total\Quote
 */
class StoreCredit extends \Amasty\StoreCredit\Model\Total\Quote\StoreCredit
{
    public function __construct(
        private \Magento\Framework\App\State $state,
        private \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        private \Amasty\StoreCredit\Model\ConfigProvider $configProvider,
        private \Amasty\StoreCredit\Model\Total\Quote\Collectors\QuoteCollector $quoteCollectorPool,
        private \Amasty\StoreCredit\Api\StoreCreditRepositoryInterface $storeCreditRepository
    ) {
        $this->setCode('amstorecredit');
        parent::__construct($state, $priceCurrency, $configProvider, $quoteCollectorPool, $storeCreditRepository);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return \Amasty\StoreCredit\Model\Total\Quote\StoreCredit
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        if ($this->configProvider->isEnabled()
            && $quote->getCustomerId()
            && $quote->getBaseToQuoteRate()
            && $total->getGrandTotal() > 0
        ) {
            $items = $shippingAssignment->getItems();
            $availableBaseCredit = $this->storeCreditRepository->getByCustomerId($quote->getCustomerId())
                ->getStoreCredit();

            if (!$items) {
                return $this;
            }

            $storeId = $quote->getStoreId();
            $currency = $quote->getQuoteCurrencyCode();
            $availableCredit = $this->priceCurrency->convertAndRound($availableBaseCredit, $storeId, $currency);

            $collector = $this->quoteCollectorPool->get($this->state->getAreaCode());
            $collector->collect($quote, $shippingAssignment, $total, $availableCredit);

            $this->calculateGrandTotal($quote, $total);
        }

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     */
    private function calculateGrandTotal(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Address\Total $total
    ): void {
        if ($quote->getData(
            \Amasty\StoreCredit\Api\Data\SalesFieldInterface::AMSC_USE
        ) && $quote->getAmstorecreditBaseAmount()
        ) {
            $grandTotal = $total->getGrandTotal() - $quote->getAmstorecreditAmount();
            $grandBaseTotal = $total->getBaseGrandTotal() - $quote->getAmstorecreditBaseAmount();
            if ($grandTotal < 0.0001) {
                $grandTotal = $grandBaseTotal = 0;
            }

            $total->setGrandTotal($grandTotal);
            $total->setBaseGrandTotal($grandBaseTotal);
        } else {
            $quote->setData(\Amasty\StoreCredit\Api\Data\SalesFieldInterface::AMSC_USE, 0);
        }
    }
}
