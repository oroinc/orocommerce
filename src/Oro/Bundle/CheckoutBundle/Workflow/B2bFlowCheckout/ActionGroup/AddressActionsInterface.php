<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * Checkout workflow Address-related actions.
 */
interface AddressActionsInterface
{
    public function updateBillingAddress(Checkout $checkout, bool $disallowShippingAddressEdit = false): bool;

    public function updateShippingAddress(Checkout $checkout): void;

    public function duplicateOrderAddress(OrderAddress $address): OrderAddress;

    public function actualizeAddresses(Checkout $checkout, Order $order): void;
}
