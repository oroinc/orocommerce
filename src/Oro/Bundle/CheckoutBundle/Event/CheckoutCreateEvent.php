<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Fires within checkout creation.
 */
final class CheckoutCreateEvent extends Event
{
    public const string NAME = 'oro_checkout.create';

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
