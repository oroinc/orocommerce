<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Checkout workflow Order-related actions.
 */
interface OrderActionsInterface
{
    public function placeOrder(Checkout $checkout): Order;

    public function flushOrder(Order $order): void;

    public function createOrderByCheckout(
        Checkout $checkout,
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress
    ): Order;

    public function sendConfirmationEmail(Checkout $checkout, Order $order): void;
}
