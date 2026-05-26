<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class UpdateSourceBySku
{
    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    private $productRepository;

    /**
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @param int $quantity
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException|\Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($sku, $sourceCode, $quantity)
    {
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSku($sku);
        $sourceItem->setSourceCode($sourceCode);
        $sourceItem->setQuantity($quantity);
        $sourceItem->setStatus(1);
        $this->sourceItemsSave->execute([$sourceItem]);
        $product = $this->productRepository->get($sku);
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        return $stockItem->getId();
    }
}
