<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Checkout workflow actions to be executed to create split orders.
 */
interface SplitOrderActionsInterface
{
    /**
     * @param Checkout   $checkout
     * @param array|null $groupedLineItemsIds ['product.owner:1' => ['sku-1:item', ...], ...]
     *
     * @return Order
     */
    public function placeOrder(Checkout $checkout, ?array $groupedLineItemsIds): Order;

    /**
     * @param Checkout $checkout
     * @param Order    $order
     * @param array    $groupedLineItemsIds ['product.owner:1' => ['sku-1:item', ...], ...]
     */
    public function createChildOrders(Checkout $checkout, Order $order, array $groupedLineItemsIds): void;
}
