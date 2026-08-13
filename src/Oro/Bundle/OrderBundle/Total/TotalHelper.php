<?php

namespace Oro\Bundle\OrderBundle\Total;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Provides methods to calculate totals, subtotals and discounts for an order and its line items.
 */
class TotalHelper
{
    private TotalProcessorProvider $totalProvider;
    private LineItemSubtotalProvider $lineItemSubtotalProvider;
    private DiscountSubtotalProvider $discountSubtotalProvider;
    private RateConverterInterface $rateConverter;

    public function __construct(
        TotalProcessorProvider $totalProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider,
        DiscountSubtotalProvider $discountSubtotalProvider,
        RateConverterInterface $rateConverter
    ) {
        $this->totalProvider = $totalProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->discountSubtotalProvider = $discountSubtotalProvider;
        $this->rateConverter = $rateConverter;
    }

    public function fill(Order $order): void
    {
        if (!$order->getSubOrders()->isEmpty()) {
            $this->fillDiscounts($order);
            $this->fillSubtotals($order);
            $this->fillTotal($order);

            return;
        }

        // Calculate the whole subtotal-provider chain once and reuse the result for the order
        // subtotal, the order-level discount and the total, instead of recomputing each part.
        $subtotals = $this->totalProvider->enableRecalculation()->getSubtotals($order);

        $this->fillDiscounts($order, $subtotals);
        $this->fillSubtotals($order, $subtotals);
        $this->fillTotal($order, $subtotals);
    }

    public function fillSubtotals(Order $order, ?ArrayCollection $subtotals = null): void
    {
        if (!$order->getSubOrders()->isEmpty()) {
            $subTotalAmount = 0;
            foreach ($order->getSubOrders() as $subOrder) {
                $subTotalAmount += $subOrder->getSubtotal();
            }
            $order->setSubtotalObject(MultiCurrency::create($subTotalAmount, $order->getCurrency()));

            return;
        }

        $subtotal = null !== $subtotals
            ? $this->extractLineItemsSubtotal($order, $subtotals)
            : $this->lineItemSubtotalProvider->getSubtotal($order);

        $order->setSubtotalObject($this->createBaseMultiCurrency($subtotal->getAmount(), $subtotal->getCurrency()));

        if ($subtotal->getAmount() > 0) {
            foreach ($order->getDiscounts() as $discount) {
                if ($discount->getType() === OrderDiscount::TYPE_AMOUNT) {
                    $discount->setPercent($this->calculatePercent($subtotal, $discount));
                }
            }
        }
    }

    public function fillDiscounts(Order $order, ?ArrayCollection $subtotals = null): void
    {
        if (!$order->getSubOrders()->isEmpty()) {
            $discountSubtotalAmount = new Price();
            foreach ($order->getSubOrders() as $subOrder) {
                $subOrderDiscount = $subOrder->getTotalDiscounts();
                if ($subOrderDiscount) {
                    $newAmount = $subOrderDiscount->getValue() + (float)$discountSubtotalAmount->getValue();
                    $discountSubtotalAmount->setValue($newAmount);
                }
            }
            $order->setTotalDiscounts($discountSubtotalAmount);

            return;
        }

        $discountSubtotals = null !== $subtotals
            ? $subtotals->filter(
                static fn (Subtotal $subtotal) => $subtotal->getName() === DiscountSubtotalProvider::NAME
            )
            : $this->discountSubtotalProvider->getSubtotal($order);

        $order->setTotalDiscounts($this->sumDiscounts($discountSubtotals));
    }

    public function fillTotal(Order $order, ?ArrayCollection $subtotals = null): void
    {
        $order->setTotalObject($this->calculateTotal($order, $subtotals));
    }

    public function calculateTotal(Order $order, ?ArrayCollection $subtotals = null): MultiCurrency
    {
        if (!$order->getSubOrders()->isEmpty()) {
            $totalAmount = 0;
            foreach ($order->getSubOrders() as $subOrder) {
                $totalAmount += $subOrder->getTotal();
            }

            return $this->createBaseMultiCurrency($totalAmount, $order->getCurrency());
        }

        if (null !== $subtotals) {
            $total = $this->totalProvider->getTotalForSubtotals($order, $subtotals);
        } else {
            try {
                $total = $this->totalProvider->enableRecalculation()->getTotal($order);
            } finally {
                $this->totalProvider->disableRecalculation();
            }
        }

        return $this->createBaseMultiCurrency($total->getAmount(), $total->getCurrency());
    }

    /**
     * Finds the line items subtotal in the already calculated subtotals collection,
     * falling back to a dedicated calculation when it is not present.
     */
    private function extractLineItemsSubtotal(Order $order, ArrayCollection $subtotals): Subtotal
    {
        foreach ($subtotals as $subtotal) {
            if ($subtotal->getName() === LineItemSubtotalProvider::NAME) {
                return $subtotal;
            }
        }

        return $this->lineItemSubtotalProvider->getSubtotal($order);
    }

    /**
     * @param iterable<Subtotal> $discountSubtotals
     */
    private function sumDiscounts(iterable $discountSubtotals): Price
    {
        $discountSubtotalAmount = new Price();
        foreach ($discountSubtotals as $discountSubtotal) {
            $newAmount = $discountSubtotal->getAmount() + (float)$discountSubtotalAmount->getValue();
            $discountSubtotalAmount->setValue($newAmount);
        }

        return $discountSubtotalAmount;
    }

    private function createBaseMultiCurrency(float $amount, ?string $currency): MultiCurrency
    {
        $multiCurrency = MultiCurrency::create($amount, $currency);
        $multiCurrency->setBaseCurrencyValue($this->rateConverter->getBaseCurrencyAmount($multiCurrency));

        return $multiCurrency;
    }

    private function calculatePercent(Subtotal $subtotal, OrderDiscount $discount): float
    {
        return (float)($discount->getAmount() / $subtotal->getAmount() * 100);
    }
}
