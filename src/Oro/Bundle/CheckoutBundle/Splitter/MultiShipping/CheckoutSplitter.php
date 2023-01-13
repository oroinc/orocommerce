<?php

namespace Oro\Bundle\CheckoutBundle\Splitter\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;

/**
 * Split checkout by line items groups.
 */
class CheckoutSplitter
{
    private CheckoutFactoryInterface $checkoutFactory;

    public function __construct(CheckoutFactoryInterface $checkoutFactory)
    {
        $this->checkoutFactory = $checkoutFactory;
    }

    public function split(Checkout $checkout, array $groupedLineItems): array
    {
        $groupedCheckouts = [];
        foreach ($groupedLineItems as $key => $lineItemsGroup) {
            $splitCheckout = $this->checkoutFactory->createCheckout($checkout, $lineItemsGroup);
            $groupedCheckouts[$key] = $splitCheckout;
        }

        return $groupedCheckouts;
    }
}
