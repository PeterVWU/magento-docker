<?php

declare(strict_types=1);

namespace Vapewholesaleusa\AheadworksRewardPoints\Block\Product\View;

use Aheadworks\RewardPoints\Api\CustomerRewardPointsManagementInterface;
use Aheadworks\RewardPoints\Model\Calculator\RateCalculator;
use Aheadworks\RewardPoints\Model\CategoryAllowed;
use Aheadworks\RewardPoints\Model\Config;
use Aheadworks\RewardPoints\Model\Config\Frontend\Label\Resolver as LabelResolver;
use Aheadworks\RewardPoints\Model\Validator\Product\Price\Discount\Checker;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Discount
 * @package Vapewholesaleusa\AheadworksRewardPoints\Block\Product\View
 */
class Discount extends \Aheadworks\RewardPoints\Block\Product\View\Discount
{
    /**
     * @param Context $context
     * @param CustomerRewardPointsManagementInterface $customerRewardPointsService
     * @param Session $customerSession
     * @param Config $config
     * @param RateCalculator $rateCalculator
     * @param PriceHelper $priceHelper
     * @param CategoryAllowed $categoryAllowed
     * @param ProductRepositoryInterface $productRepository
     * @param Checker $discountChecker
     * @param LabelResolver $labelResolver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        private readonly \Aheadworks\RewardPoints\Api\CustomerRewardPointsManagementInterface $customerRewardPointsService,
        private readonly \Magento\Customer\Model\Session $customerSession,
        private readonly \Aheadworks\RewardPoints\Model\Config $config,
        private readonly \Aheadworks\RewardPoints\Model\Calculator\RateCalculator $rateCalculator,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        private readonly \Aheadworks\RewardPoints\Model\CategoryAllowed $categoryAllowed,
        private readonly \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private readonly \Aheadworks\RewardPoints\Model\Validator\Product\Price\Discount\Checker $discountChecker,
        \Aheadworks\RewardPoints\Model\Config\Frontend\Label\Resolver $labelResolver,
        private readonly \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerRewardPointsService,
            $customerSession,
            $config,
            $rateCalculator,
            $priceHelper,
            $categoryAllowed,
            $productRepository,
            $discountChecker,
            $labelResolver,
            $data
        );
    }

    /**
     * Retrieve config value for Display block with discount information
     *
     * @return boolean
     */
    public function isDisplayBlock()
    {
        $customerRewardPointsOnceMinBalance = $this->customerRewardPointsService
            ->getCustomerRewardPointsOnceMinBalance($this->customerSession->getId());
        $customerRewardPointsSpendRate = $this->customerRewardPointsService
            ->isCustomerRewardPointsSpendRate($this->customerSession->getId());
        $customerRewardPointsSpendRateByGroup = $this->customerRewardPointsService
            ->isCustomerRewardPointsSpendRateByGroup($this->customerSession->getId());
        if ($this->config->isDisplayDiscountInfoBlock()
            && $this->isAllowedCategoriesForSpend()
            && $customerRewardPointsOnceMinBalance == 0
            && $customerRewardPointsSpendRateByGroup
            && $customerRewardPointsSpendRate) {
            try {
                $product = $this->productRepository->getById($this->getRequest()->getParam('id'));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->logger->warning('product doesn\'t exist with id' . (string) $this->getRequest()->getParam('id'));
                return false;
            }
        } else {
            return false;
        }
        return !$this->discountChecker->validateProductForSpend($product);
    }

    /**
     * Get customer available amount
     *
     * @return float
     */
    private function getAvailableAmount()
    {
        $points = $this->getAvailablePoints();
        if ($points > 0) {
            return $this->rateCalculator->calculateRewardDiscount($this->customerSession->getId(), $points);
        }

        return 0;
    }

    /**
     * Is allowed category products for spend
     *
     * @return boolean
     */
    private function isAllowedCategoriesForSpend()
    {
        return $this->categoryAllowed->isAllowedCategoryForSpendPoints($this->getProduct()->getCategoryIds());
    }

    /**
     * Retrieve current product
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProduct()
    {
        $productId = $this->getRequest()->getParam('product_id', null)
            ? $this->getRequest()->getParam('product_id')
            : $this->getRequest()->getParam('id');
        return $this->productRepository->getById($productId);
    }
}
