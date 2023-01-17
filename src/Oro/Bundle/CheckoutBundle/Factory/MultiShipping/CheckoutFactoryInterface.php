<?php

namespace Oro\Bundle\CheckoutBundle\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

/**
 * Represents a factory responsible for creating checkouts from a specific checkout.
 */
interface CheckoutFactoryInterface
{
    /**
     * Creates a new checkout from the given checkout.
     *
     * @param Checkout                   $source
     * @param iterable<CheckoutLineItem> $lineItems
     *
     * @return Checkout
     */
    public function createCheckout(Checkout $source, iterable $lineItems): Checkout;
}
