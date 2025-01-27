<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires within checkout data actualization.
 */
final class CheckoutActualizeEvent extends Event
{
    public function __construct(
        private readonly Checkout $checkout,
        private readonly array $sourceCriteria,
        private readonly array $checkoutData = []
    ) {
    }

    public function getCheckout(): Checkout
    {
        return $this->checkout;
    }

    public function getSourceCriteria(): array
    {
        return $this->sourceCriteria;
    }

    public function getCheckoutData(): array
    {
        return $this->checkoutData;
    }
}
