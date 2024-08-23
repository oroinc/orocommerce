<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Actualizes user currency by a checkout.
 */
interface ActualizeCurrencyInterface
{
    public function execute(Checkout $checkout): void;
}
