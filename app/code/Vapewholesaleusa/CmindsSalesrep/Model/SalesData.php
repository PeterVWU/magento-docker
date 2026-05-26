<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CmindsSalesrep\Model;

class SalesData extends \Magento\Framework\Model\AbstractExtensibleModel implements \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
{
    /**
     * Get sales representative ID
     *
     * @return string|null Sales rep ID or null if not set
     */
    public function getSalesrepId(): ?string
    {
        return $this->getData(self::SALESREP_ID);
    }

    /**
     * Set sales representative ID
     *
     * @param string $salesrepId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setSalesrepId(string $salesrepId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::SALESREP_ID, $salesrepId);
    }

    /**
     * Get associated order ID
     *
     * @return string|int|null Order ID or null if not set
     */
    public function getOrderId(): string|int|null
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set associated order ID
     *
     * @param string|int|null $orderId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setOrderId(string|int|null $orderId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get representative ID
     *
     * @return string|int|null Representative ID or null if not set
     */
    public function getRepId(): string|int|null
    {
        return $this->getData(self::REP_ID);
    }

    /**
     * Set representative ID
     *
     * @param string|int|null $repId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setRepId(string|int|null $repId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::REP_ID, $repId);
    }

    /**
     * Get representative name
     *
     * @return string|null Representative name or null if not set
     */
    public function getRepName(): ?string
    {
        return $this->getData(self::REP_NAME);
    }

    /**
     * Set representative name
     *
     * @param string|null $repName
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setRepName(?string $repName): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::REP_NAME, $repName);
    }

    /**
     * Get representative email
     *
     * @return string|null Representative email or null if not set
     */
    public function getRepEmail(): ?string
    {
        return $this->getData(self::REP_EMAIL);
    }

    /**
     * Set representative email
     *
     * @param string|null $repEmail
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setRepEmail(?string $repEmail): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::REP_EMAIL, $repEmail);
    }

    /**
     * Get representative commission amount
     *
     * @return float|string|null Commission amount or null if not set
     */
    public function getRepCommissionEarned(): float|string|null
    {
        return $this->getData(self::REP_COMMISSION_EARNED);
    }

    /**
     * Set representative commission amount
     *
     * @param float|string|null $amount
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setRepCommissionEarned(float|string|null $amount): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::REP_COMMISSION_EARNED, $amount);
    }

    /**
     * Get representative commission status
     *
     * @return string|null Status or null if not set
     */
    public function getRepCommissionStatus(): ?string
    {
        return $this->getData(self::REP_COMMISSION_STATUS);
    }

    /**
     * Set representative commission status
     *
     * @param string|null $status
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setRepCommissionStatus(?string $status): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::REP_COMMISSION_STATUS, $status);
    }

    /**
     * Get manager ID
     *
     * @return string|int|null Manager ID or null if not set
     */
    public function getManagerId(): string|int|null
    {
        return $this->getData(self::MANAGER_ID);
    }

    /**
     * Set manager ID
     *
     * @param string|int|null $managerId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setManagerId(string|int|null $managerId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::MANAGER_ID, $managerId);
    }

    /**
     * Get manager name
     *
     * @return string|null Manager name or null if not set
     */
    public function getManagerName(): ?string
    {
        return $this->getData(self::MANAGER_NAME);
    }

    /**
     * Set manager name
     *
     * @param string|null $managerName
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setManagerName(?string $managerName): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::MANAGER_NAME, $managerName);
    }

    /**
     * Get manager commission amount
     *
     * @return float|string|null Commission amount or null if not set
     */
    public function getManagerCommissionEarned(): float|string|null
    {
        return $this->getData(self::MANAGER_COMMISSION_EARNED);
    }

    /**
     * Set manager commission amount
     *
     * @param float|string|null $amount
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setManagerCommissionEarned(float|string|null $amount): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::MANAGER_COMMISSION_EARNED, $amount);
    }

    /**
     * Get manager commission status
     *
     * @return string|null Status or null if not set
     */
    public function getManagerCommissionStatus(): ?string
    {
        return $this->getData(self::MANAGER_COMMISSION_STATUS);
    }

    /**
     * Set manager commission status
     *
     * @param string|null $status
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setManagerCommissionStatus(?string $status): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::MANAGER_COMMISSION_STATUS, $status);
    }

    /**
     * Get coordinator commission amount
     *
     * @return float|string|null Commission amount or null if not set
     */
    public function getCoordinatorCommissionEarned(): float|string|null
    {
        return $this->getData(self::COORDINATOR_COMMISSION_EARNED);
    }

    /**
     * Set coordinator commission amount
     *
     * @param float|string|null $amount
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setCoordinatorCommissionEarned(float|string|null $amount): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::COORDINATOR_COMMISSION_EARNED, $amount);
    }

    /**
     * Get coordinator commission status
     *
     * @return string|null Status or null if not set
     */
    public function getCoordinatorCommissionStatus(): ?string
    {
        return $this->getData(self::COORDINATOR_COMMISSION_STATUS);
    }

    /**
     * Set coordinator commission status
     *
     * @param string|null $status
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
     */
    public function setCoordinatorCommissionStatus(?string $status): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface
    {
        return $this->setData(self::COORDINATOR_COMMISSION_STATUS, $status);
    }
}
