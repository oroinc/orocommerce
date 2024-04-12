<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

interface UpdateShippingPriceInterface
{
    public function execute(Checkout $checkout): void;
}
