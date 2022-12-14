<?php

namespace Oro\Bundle\CheckoutBundle\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Provides basic method for factories responsible for creating checkouts from checkout source.
 */
interface CheckoutFactoryInterface
{
    /**
     * Implement logic to create new checkout from checkout source.
     *
     * @param Checkout $checkoutSource
     * @param iterable $lineItems
     * @return Checkout
     */
    public function createCheckout(Checkout $checkoutSource, iterable $lineItems): Checkout;
}
