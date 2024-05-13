<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

interface UpdateCheckoutStateInterface
{
    public function execute(
        Checkout $checkout,
        string $stateToken,
        ?bool $updateCheckoutState = false,
        ?bool $forceUpdate = false
    ): array;
}
