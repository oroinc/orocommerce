<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;

/**
 * Set checkout shipping cost
 */
class UpdateShippingPrice implements UpdateShippingPriceInterface
{
    public function __construct(
        private CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider
    ) {
    }

    public function execute(Checkout $checkout): void
    {
        $checkout->setShippingCost(
            $this->checkoutShippingMethodsProvider->getPrice($checkout)
        );
    }
}
