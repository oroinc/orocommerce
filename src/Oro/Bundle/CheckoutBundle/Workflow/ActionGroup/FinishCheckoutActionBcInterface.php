<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Finish Checkout BC actions.
 */
interface FinishCheckoutActionBcInterface
{
    /**
     * @deprecated is available only for 6.0.x, will be removed in the next minor release.
     * Adds context to extendable action. Consider to move to determined data available in the event instead of context.
     */
    public function finishCheckoutBC(
        Checkout $checkout,
        Order $order,
        bool $autoRemoveSource = false,
        bool $allowManualSourceRemove = false,
        bool $removeSource = false,
        bool $clearSource = false,
        mixed $context = null
    ): void;
}
