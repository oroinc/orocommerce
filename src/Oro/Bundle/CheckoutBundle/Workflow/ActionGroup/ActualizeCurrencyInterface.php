<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Actualizes user currency by checkout.
 */
interface ActualizeCurrencyInterface
{
    public function execute(Checkout $checkout): void;
}
