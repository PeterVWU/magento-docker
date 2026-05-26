<?php

declare(strict_types=1);

namespace Vapewholesaleusa\OrderSource\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceExtensionInterface;
use Vapewholesaleusa\OrderSource\Api\Data\OrderSourceInterface;

class OrderSource extends AbstractExtensibleObject implements OrderSourceInterface
{
    public function getEntityId(): ?int
    {
        return $this->_get(self::ENTITY_ID);
    }

    public function setEntityId(int $entity_id): OrderSourceInterface
    {
        return $this->setData(self::ENTITY_ID, $entity_id);
    }

    public function getOrderId(): ?int
    {
        return $this->_get(self::ORDER_ID);
    }

    public function setOrderId(int $order_id): OrderSourceInterface
    {
        return $this->setData(self::ORDER_ID, (int)$order_id);
    }

    public function getSku(): ?string
    {
        return $this->_get(self::SKU);
    }

    public function setSku(string $sku): OrderSourceInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    public function getSourceCode(): ?string
    {
        return $this->_get(self::SOURCE_CODE);
    }

    public function setSourceCode(string $source_code): OrderSourceInterface
    {
        return $this->setData(self::SOURCE_CODE, $source_code);
    }

    public function getQty(): ?float
    {
        return $this->_get(self::QTY);
    }

    public function setQty(string $qty): OrderSourceInterface
    {
        return $this->setData(self::QTY, (float)$qty);
    }

    public function getOrderIncId(): ?string
    {
        return $this->_get(self::ORDER_INC_ID);
    }

    public function setOrderIncId(string $order_inc_id): OrderSourceInterface
    {
        return $this->setData(self::ORDER_INC_ID, $order_inc_id);
    }

    public function getItemId(): ?int
    {
        return $this->_get(self::ITEM_ID);
    }

    public function setItemId(int $item_id): OrderSourceInterface
    {
        return $this->setData(self::ITEM_ID, (int)$item_id);
    }

    public function getQtyShipped(): ?int
    {
        return $this->_get(self::QTY_SHIPPED);
    }

    public function setQtyShipped(int $qty_shipped): OrderSourceInterface
    {
        return $this->setData(self::QTY_SHIPPED, $qty_shipped);
    }

    public function getStatus(): ?int
    {
        return $this->_get(self::STATUS);
    }

    public function setStatus(int $status): OrderSourceInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getExtensionAttributes(): ?OrderSourceExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    public function setExtensionAttributes(
        OrderSourceExtensionInterface $extensionAttributes
    ): static
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
