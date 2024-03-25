<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;


use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Checkout workflow Order-related actions.
 */
interface OrderActionsInterface
{
    public function placeOrder(Checkout $checkout): array;

    public function flushOrder(Order $order): void;

    public function createOrderByCheckout(
        Checkout $checkout,
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress
    ): array;

    public function sendConfirmationEmail(Checkout $checkout, Order $order): void;
}
