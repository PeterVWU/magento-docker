<?php

declare(strict_types=1);

namespace Vapewholesaleusa\PaymentRequest\Model;

class TokenProvider
{
    /**
     * @var string
     */
    private string $token;

    /**
     * Set token for rendering in the form.
     *
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Get token for use in the template.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token ?? null;
    }
}
