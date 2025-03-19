<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Provider\SubtotalProvider;

/**
 * Event listener for filling Order::subtotalWithDiscounts
 */
class OrderSubtotalWithDiscountsListener
{
    use FeatureCheckerHolderTrait;

    private ?RateConverterInterface $rateConverter = null;

    public function __construct(
        private SubtotalProviderInterface $subtotalProvider
    ) {
    }

    public function setRateConverter(RateConverterInterface $rateConverter): self
    {
        $this->rateConverter = $rateConverter;

        return $this;
    }

    public function prePersist(Order $order, LifecycleEventArgs $event): void
    {
        $this->updateOrderSubtotal($order);
    }

    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        $this->updateOrderSubtotal($order);
    }

    private function updateOrderSubtotal(Order $order): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $orderSubtotal = $this->subtotalProvider->isSupported($order)
            ? $this->calculateSubtotalWithDiscount($order)
            : $order->getSubtotal();

        $orderSubtotal -= $this->calculateOrderDiscounts($order);
        $orderSubtotal = max($orderSubtotal, 0.0);

        $orderSubtotalObject = MultiCurrency::create($orderSubtotal, $order->getCurrency());
        $orderSubtotalObject->setBaseCurrencyValue($this->rateConverter->getBaseCurrencyAmount($orderSubtotalObject));

        $order->setSubtotalDiscountObject($orderSubtotalObject);
        $order->updateMultiCurrencyFields();
    }

    private function calculateSubtotalWithDiscount(Order $order): float
    {
        $subtotals = $this->subtotalProvider->getSubtotal($order);
        $discountSubtotal = $subtotals[SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL];
        $orderSubtotal = $order->getSubtotal();

        return match ($discountSubtotal->getOperation()) {
            Subtotal::OPERATION_ADD => $orderSubtotal + $discountSubtotal->getAmount(),
            Subtotal::OPERATION_SUBTRACTION => $orderSubtotal - $discountSubtotal->getAmount(),
            default => $orderSubtotal,
        };
    }

    private function calculateOrderDiscounts(Order $order): float
    {
        return array_reduce(
            $order->getDiscounts()->toArray(),
            static fn (float $accum, $discount) => $accum + $discount->getAmount(),
            0
        );
    }
}
