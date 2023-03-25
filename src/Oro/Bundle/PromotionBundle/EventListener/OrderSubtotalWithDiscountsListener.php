<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Provider\SubtotalProvider;

/**
 * Event listener for filling Order::subtotalWithDiscounts
 */
class OrderSubtotalWithDiscountsListener
{
    private SubtotalProviderInterface $subtotalProvider;

    public function __construct(SubtotalProviderInterface $subtotalProvider)
    {
        $this->subtotalProvider = $subtotalProvider;
    }

    public function prePersist(Order $order, LifecycleEventArgs $event): void
    {
        $orderSubtotal = $order->getSubtotal();
        if ($this->subtotalProvider->isSupported($order)) {
            $orderSubtotal = $this->calculateSubtotalWithDiscount($order);
        }
        $orderSubtotal -= $this->calculateOrderDiscounts($order);
        $order->setSubtotalWithDiscounts($orderSubtotal > 0 ? $orderSubtotal : 0.0);
    }

    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        $orderSubtotal = $order->getSubtotal();
        if ($this->subtotalProvider->isSupported($order)) {
            $orderSubtotal = $this->calculateSubtotalWithDiscount($order);
        }
        $orderSubtotal -= $this->calculateOrderDiscounts($order);
        $order->setSubtotalWithDiscounts($orderSubtotal > 0 ? $orderSubtotal : 0.0);
    }

    private function calculateSubtotalWithDiscount(Order $order): float
    {
        $subtotals = $this->subtotalProvider->getSubtotal($order);
        $discountSubtotal = $subtotals[SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL];
        $discountSubtotalAmount = $discountSubtotal->getAmount();
        $orderSubtotal = $order->getSubtotal();

        switch ($discountSubtotal->getOperation()) {
            case Subtotal::OPERATION_ADD:
                $orderSubtotal += $discountSubtotalAmount;
                break;
            case Subtotal::OPERATION_SUBTRACTION:
                $orderSubtotal -= $discountSubtotalAmount;
                break;
        }

        return $orderSubtotal;
    }

    private function calculateOrderDiscounts(Order $order): float
    {
        $discountsArray = $order->getDiscounts()->toArray();
        return array_reduce(
            $discountsArray,
            static fn (float $accum, OrderDiscount $currentDiscount) => $accum + $currentDiscount->getAmount(),
            0
        );
    }
}
