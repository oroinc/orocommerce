<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Set checkout shipping cost
 */
interface UpdateShippingPriceInterface
{
    public function execute(Checkout $checkout): void;
}
