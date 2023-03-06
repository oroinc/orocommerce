<?php

namespace Oro\Bundle\CheckoutBundle\Splitter\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;

/**
 * Splits checkout by line items groups.
 */
class CheckoutSplitter
{
    private CheckoutFactoryInterface $checkoutFactory;

    public function __construct(CheckoutFactoryInterface $checkoutFactory)
    {
        $this->checkoutFactory = $checkoutFactory;
    }

    /**
     * @param Checkout $checkout
     * @param array    $groupedLineItems
     *
     * @return Checkout[] ['product.owner:1' => checkout, ...]
     */
    public function split(Checkout $checkout, array $groupedLineItems): array
    {
        $groupedCheckouts = [];
        foreach ($groupedLineItems as $groupingPath => $lineItems) {
            $groupedCheckouts[$groupingPath] = $this->checkoutFactory->createCheckout($checkout, $lineItems);
        }

        return $groupedCheckouts;
    }
}
