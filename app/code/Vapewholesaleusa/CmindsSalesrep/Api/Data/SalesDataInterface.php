<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CmindsSalesrep\Api\Data;

interface SalesDataInterface
{
    /**
     * Constants
     */
    public const SALESREP_ID                   = 'salesrep_id';
    public const ORDER_ID                      = 'order_id';
    public const REP_ID                        = 'rep_id';
    public const REP_NAME                      = 'rep_name';
    public const REP_EMAIL                     = 'rep_email';
    public const REP_COMMISSION_EARNED         = 'rep_commission_earned';
    public const REP_COMMISSION_STATUS         = 'rep_commission_status';
    public const MANAGER_ID                    = 'manager_id';
    public const MANAGER_NAME                  = 'manager_name';
    public const MANAGER_COMMISSION_EARNED     = 'manager_commission_earned';
    public const MANAGER_COMMISSION_STATUS     = 'manager_commission_status';
    public const COORDINATOR_COMMISSION_EARNED = 'coordinator_commission_earned';
    public const COORDINATOR_COMMISSION_STATUS = 'coordinator_commission_status';

    /**
     * Get sales representative identifier
     *
     * @return string|null Sales rep ID or null if not set
     */
    public function getSalesrepId(): ?string;

    /**
     * Set sales representative identifier
     *
     * @param string $salesrepId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setSalesrepId(string $salesrepId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get associated order identifier
     *
     * @return string|int|null Order ID or null if not set
     */
    public function getOrderId(): int|string|null;

    /**
     * Set associated order identifier
     *
     * @param string|int|null $orderId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setOrderId(string|int|null $orderId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get representative identifier
     *
     * @return string|int|null Representative ID or null if not set
     */
    public function getRepId(): string|int|null;

    /**
     * Set representative identifier
     *
     * @param string|int|null $repId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setRepId(string|int|null $repId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get representative full name
     *
     * @return string|null Representative name or null if not set
     */
    public function getRepName(): ?string;

    /**
     * Set representative full name
     *
     * @param string|null $repName
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setRepName(?string $repName): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get representative email
     *
     * @return string|null Representative email or null if not set
     */
    public function getRepEmail(): ?string;

    /**
     * Set representative email
     *
     * @param string $repEmail
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setRepEmail(string $repEmail): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get earned commission amount for representative
     *
     * @return float|string|null Commission amount or null if not set
     */
    public function getRepCommissionEarned(): float|string|null;

    /**
     * Set earned commission amount for representative
     *
     * @param float|string|null $amount
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setRepCommissionEarned(float|string|null $amount): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get commission status for representative
     *
     * @return string|null Status code or null if not set
     */
    public function getRepCommissionStatus(): ?string;

    /**
     * Set commission status for representative
     *
     * @param string|null $status
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setRepCommissionStatus(?string $status): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get manager identifier
     *
     * @return string|int|null Manager ID or null if not set
     */
    public function getManagerId(): string|int|null;

    /**
     * Set manager identifier
     *
     * @param string|int|null $managerId
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setManagerId(string|int|null $managerId): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get manager full name
     *
     * @return string|null Manager name or null if not set
     */
    public function getManagerName(): ?string;

    /**
     * Set manager full name
     *
     * @param string|null $managerName
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setManagerName(?string $managerName): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get earned commission amount for manager
     *
     * @return float|string|null Commission amount or null if not set
     */
    public function getManagerCommissionEarned(): float|string|null;

    /**
     * Set earned commission amount for manager
     *
     * @param float|string|null $amount
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setManagerCommissionEarned(float|string|null $amount): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get commission status for manager
     *
     * @return string|null Status code or null if not set
     */
    public function getManagerCommissionStatus(): ?string;

    /**
     * Set commission status for manager
     *
     * @param string|null $status
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setManagerCommissionStatus(?string $status): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get earned commission amount for coordinator
     *
     * @return float|string|null Commission amount or null if not set
     */
    public function getCoordinatorCommissionEarned(): float|string|null;

    /**
     * Set earned commission amount for coordinator
     *
     * @param float|string|null $amount
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setCoordinatorCommissionEarned(float|string|null $amount): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;

    /**
     * Get commission status for coordinator
     *
     * @return string|null Status code or null if not set
     */
    public function getCoordinatorCommissionStatus(): ?string;

    /**
     * Set commission status for coordinator
     *
     * @param string|null $status
     * @return \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
     */
    public function setCoordinatorCommissionStatus(?string $status): \Vapewholesaleusa\CmindsSalesrep\Api\Data\SalesDataInterface;
}
