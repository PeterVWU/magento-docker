<?php

declare(strict_types=1);

namespace Vapewholesaleusa\MageWorxOrderEditor\Model\Edit;

use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;

class Thumbnail extends \MageWorx\OrderEditor\Model\Edit\Thumbnail
{
    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Catalog\Api\Data\ProductInterfaceFactory $productInterfaceFactory
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Api\Data\ProductInterfaceFactory $productInterfaceFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        private readonly Configurable $configurableType,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct(
            $productResource,
            $productInterfaceFactory,
            $imageHelper,
            $productRepository
        );
    }

    /**
     * Resolve product thumbnail without loading full product model.
     *
     * @param \Magento\Sales\Api\Data\OrderItemInterface $item
     * @return \Magento\Catalog\Helper\Image|bool
     */
    public function getImgByItem(\Magento\Sales\Api\Data\OrderItemInterface $item)
    {
        try {
            $storeId  = (int)$item->getStoreId();
            if ($item->getProductType() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $sku = $item->getSku();
                if ($sku === '') {
                    return false;
                }
                $entityId = (int)$this->productResource->getIdBySku($sku);
            } else {
                $entityId = (int)$item->getProductId();
            }

            if ($entityId <= 0) {
                return false;
            }

            $getAttr = function (int $pid, string $code) use ($storeId): string {
                $val = $this->productResource->getAttributeRawValue($pid, $code, $storeId);
                if (is_array($val)) {
                    $val = reset($val);
                }
                $val = is_scalar($val) ? (string)$val : '';

                if ($val === '' || $val === 'no_selection') {
                    $val = $this->productResource->getAttributeRawValue($pid, $code, 0);
                    if (\is_array($val)) {
                        $val = \reset($val);
                    }
                    $val = \is_scalar($val) ? (string)$val : '';
                }
                return $val !== 'no_selection' ? $val : '';
            };

            $file = $getAttr($entityId, 'thumbnail');
            if ($file === '') {
                $file = $getAttr($entityId, 'small_image');
            }
            if ($file === '') {
                $file = $getAttr($entityId, 'image');
            }
            if ($file === '') {
                $parentId = false;
                if ($item->getProductType() == Type::TYPE_SIMPLE) {
                    $parentIds = $this->configurableType->getParentIdsByChild($entityId);
                    $parentId = $parentIds[0] ?? false;
                }
                if ($parentId) {
                    $file = $getAttr($entityId, 'thumbnail');
                    if ($file === '') {
                        $file = $getAttr($entityId, 'small_image');
                    }
                    if ($file === '') {
                        $file = $getAttr($entityId, 'image');
                    }
                }
            }

            if ($file === '') {
                return false;
            }

            /** @var \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create();
            $product->setStoreId($storeId);
            $product->setData('thumbnail', $file);
            $product->setData('small_image', $file);
            $product->setData('image', $file);

            $image = $this->imageHelper->init($product, 'product_listing_thumbnail');
            $image->setImageFile($file);

            return $image;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->critical($e->getMessage());
            return false;
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }
}
