<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Checkout workflow actions to be executed to create split orders.
 */
interface SplitOrderActionsInterface
{
    public function createChildOrders(Checkout $checkout, Order $order, array $groupedLineItemsIds): void;
}
