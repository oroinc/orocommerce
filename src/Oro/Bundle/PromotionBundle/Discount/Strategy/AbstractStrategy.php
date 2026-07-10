<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\CurrencyBundle\Rounding\PriceRoundingService;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Component\Math\BigDecimal;
use Oro\Component\Math\RoundingMode;

/**
 * Provide default functionality for discount strategies
 */
abstract class AbstractStrategy implements StrategyInterface
{
    protected function processLineItemDiscounts(DiscountContextInterface $discountContext)
    {
        foreach ($discountContext->getLineItems() as $discountLineItem) {
            foreach ($discountLineItem->getDiscounts() as $discount) {
                $discountAmount = $discount->calculate($discountLineItem);
                $discountLineItem->addDiscountInformation(new DiscountInformation($discount, $discountAmount));

                $subtotal = $this->getSubtotalWithDiscount($discountLineItem->getSubtotal(), $discountAmount);
                $discountLineItem->setSubtotal($subtotal);
            }
        }
    }

    protected function processTotalDiscounts(DiscountContextInterface $discountContext)
    {
        foreach ($discountContext->getSubtotalDiscounts() as $discount) {
            $discountAmount = BigDecimal::of($discount->calculate($discountContext))
                ->toScale(PriceRoundingService::DEFAULT_PRECISION, RoundingMode::HALF_UP)
                ->toFloat();
            $discountContext->addSubtotalDiscountInformation(new DiscountInformation($discount, $discountAmount));

            $subtotal = $this->getSubtotalWithDiscount($discountContext->getSubtotal(), $discountAmount);
            $discountContext->setSubtotal($subtotal);

            $this->allocateTotalDiscountAmountToLineItems($discountContext, $discountAmount);
        }
    }

    protected function processShippingDiscounts(DiscountContextInterface $discountContext)
    {
        foreach ($discountContext->getShippingDiscounts() as $discount) {
            $discountAmount = $discount->calculate($discountContext);
            $discountContext->addShippingDiscountInformation(new DiscountInformation($discount, $discountAmount));

            $subtotal = $this->getSubtotalWithDiscount($discountContext->getShippingCost(), $discountAmount);
            $discountContext->setShippingCost($subtotal);
        }
    }

    /**
     * @param float $subtotal
     * @param float $discountAmount
     * @return float
     */
    protected function getSubtotalWithDiscount($subtotal, $discountAmount): float
    {
        $subtotal -= $discountAmount;
        if ($subtotal < 0.0) {
            return 0.0;
        }

        return $subtotal;
    }

    /**
     * Allocate discount amount for each Line Item as a part of Total Discount Amount
     *
     * Example:
     *
     * Given:
     * Line item 1 subtotal = 1000$
     * Line item 2 subtotal = 100$
     * Total discount amount = 10$
     *
     * Calculations:
     * Line item 1 discount amount = (1000 * 10) / (1000 + 100) = 9.09$
     * Line item 2 discount amount = (100 * 10) / (1000 + 100) = 0.91$
     *
     * Verification:
     * 9.09$ + 0.91$ = 10$ (Total discount amount value)
     */
    protected function allocateTotalDiscountAmountToLineItems(
        DiscountContextInterface $discountContext,
        float $discountAmount
    ): void {
        $lineItems = $discountContext->getLineItems();
        $lastLineItem = \array_pop($lineItems);
        $remaining = BigDecimal::of($discountAmount)
            ->toScale(PriceRoundingService::DEFAULT_PRECISION, RoundingMode::HALF_UP);

        /** @var DiscountLineItemInterface $discountLineItem */
        foreach ($lineItems as $discountLineItem) {
            $lineItemDiscountAmount = $this->calculateLineItemDiscountAmount(
                $discountContext,
                $discountLineItem,
                $discountAmount
            );

            $subtotalAfterDiscounts = $this->getSubtotalWithDiscount(
                $discountLineItem->getSubtotalAfterDiscounts(),
                $lineItemDiscountAmount
            );
            $discountLineItem->setSubtotalAfterDiscounts($subtotalAfterDiscounts);

            $remaining = $remaining->minus(BigDecimal::of($lineItemDiscountAmount));
        }

        if ($lastLineItem instanceof DiscountLineItemInterface) {
            $lastLineItem->setSubtotalAfterDiscounts(
                $this->getSubtotalWithDiscount($lastLineItem->getSubtotalAfterDiscounts(), $remaining->toFloat())
            );
        }
    }

    protected function calculateLineItemDiscountAmount(
        DiscountContextInterface $discountContext,
        DiscountLineItemInterface $discountLineItem,
        float $discountAmount
    ): float {
        $subtotal = $discountContext->getSubtotal() + $discountAmount;

        if (BigDecimal::of($subtotal)->isZero()) {
            return 0.0;
        }

        return BigDecimal::of($discountLineItem->getSubtotalAfterDiscounts())
            ->multipliedBy($discountAmount)
            ->dividedBy($subtotal, PriceRoundingService::DEFAULT_PRECISION, RoundingMode::HALF_UP)
            ->toFloat();
    }
}
