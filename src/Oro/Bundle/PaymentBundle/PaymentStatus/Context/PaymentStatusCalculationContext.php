<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Context;

/**
 * Context for payment status calculation.
 */
class PaymentStatusCalculationContext
{
    public function __construct(
        private readonly array $contextData
    ) {
    }

    public function get(string $name): mixed
    {
        return $this->contextData[$name] ?? null;
    }
}
