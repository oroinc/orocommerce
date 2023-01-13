<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Provider which define - hide or show Sub-Orders block for:
 * - frontend order view page.
 * - frontend order history page.
 */
class OrderMultiShippingLayoutProvider
{
    private ConfigProvider $multiShippingConfigProvider;

    public function __construct(ConfigProvider $multiShippingConfigProvider)
    {
        $this->multiShippingConfigProvider = $multiShippingConfigProvider;
    }

    public function getDisplaySubOrdersAvailable(Order $order): bool
    {
        return $this->multiShippingConfigProvider->isShowSubordersInOrderHistoryEnabled()
            && $this->orderHasSubOrders($order);
    }

    private function orderHasSubOrders(Order $order): bool
    {
        return null === $order->getParent() && $order->getSubOrders()->count() > 0;
    }
}
