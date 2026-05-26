<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Api;

interface OrderStatusWebhookInterface
{
    /**
     *  Updates the order status based on the webhook or API data.
     *
     * @return void
     */
    public function execute(): void;
}
