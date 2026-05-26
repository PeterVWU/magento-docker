<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface OrderSourceInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const SKU = 'sku';
    public const SOURCE_CODE = 'source_code';
    public const QTY = 'qty';
    public const ORDER_INC_ID = 'order_inc_id';
    public const ITEM_ID = 'item_id';
    public const QTY_SHIPPED = 'qty_shipped';
    public const STATUS = 'status';

    /**
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * @param int $entity_id
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setEntityId(int $entity_id): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $order_id
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setOrderId(int $order_id): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return string|null
     */
    public function getSku(): ?string;

    /**
     * @param string $sku
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setSku(string $sku): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return string|null
     */
    public function getSourceCode(): ?string;

    /**
     * @param string $source_code
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setSourceCode(string $source_code): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return string|null
     */
    public function getQty(): ?float;

    /**
     * @param string $qty
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setQty(string $qty): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return string|null
     */
    public function getOrderIncId(): ?string;

    /**
     * @param string $order_inc_id
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setOrderIncId(string $order_inc_id): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return int|null
     */
    public function getItemId(): ?int;

    /**
     * @param int $item_id
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setItemId(int $item_id): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return int|null
     */
    public function getQtyShipped(): ?int;

    /**
     * @param int $qty_shipped
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setQtyShipped(int $qty_shipped): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * @param int $status
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface
     */
    public function setStatus(int $status): \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

    /**
     * @return \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Vapewholesaleusa\OrderSource\Api\Data\OrderSourceExtensionInterface;

    /**
     * @param \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceExtensionInterface $extensionAttributes
     * @return static
     */
    public function setExtensionAttributes(
        \Vapewholesaleusa\OrderSource\Api\Data\OrderSourceExtensionInterface $extensionAttributes
    ): static;
}
