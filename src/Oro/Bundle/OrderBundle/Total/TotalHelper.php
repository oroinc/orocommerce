<?php

namespace Oro\Bundle\OrderBundle\Total;

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
        $this->fillDiscounts($order);
        $this->fillSubtotals($order);
        $this->fillTotal($order);
    }

    public function fillSubtotals(Order $order): void
    {
        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($order);

        $subtotalObject = MultiCurrency::create($subtotal->getAmount(), $subtotal->getCurrency());
        $baseSubtotal = $this->rateConverter->getBaseCurrencyAmount($subtotalObject);
        $subtotalObject->setBaseCurrencyValue($baseSubtotal);

        $order->setSubtotalObject($subtotalObject);
        if ($subtotal->getAmount() > 0) {
            foreach ($order->getDiscounts() as $discount) {
                if ($discount->getType() === OrderDiscount::TYPE_AMOUNT) {
                    $discount->setPercent($this->calculatePercent($subtotal, $discount));
                }
            }
        }
    }

    public function fillDiscounts(Order $order): void
    {
        $discountSubtotals = $this->discountSubtotalProvider->getSubtotal($order);

        $discountSubtotalAmount = new Price();
        if (\count($discountSubtotals) > 0) {
            foreach ($discountSubtotals as $discount) {
                $newAmount = $discount->getAmount() + (float)$discountSubtotalAmount->getValue();
                $discountSubtotalAmount->setValue($newAmount);
            }
        }
        $order->setTotalDiscounts($discountSubtotalAmount);
    }

    public function fillTotal(Order $order): void
    {
        $total = $this->totalProvider->enableRecalculation()->getTotal($order);
        $totalObject = MultiCurrency::create($total->getAmount(), $total->getCurrency());
        $baseTotal = $this->rateConverter->getBaseCurrencyAmount($totalObject);
        $totalObject->setBaseCurrencyValue($baseTotal);
        $order->setTotalObject($totalObject);
    }

    private function calculatePercent(Subtotal $subtotal, OrderDiscount $discount): float
    {
        return (float)($discount->getAmount() / $subtotal->getAmount() * 100);
    }
}
