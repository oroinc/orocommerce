<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;
use Oro\Component\Math\BigDecimal;

/**
 * Provides default functionality for discount strategies.
 */
abstract class AbstractStrategy implements StrategyInterface
{
    protected function processLineItemDiscounts(DiscountContextInterface $discountContext)
    {
        $discountLineItems = $discountContext->getLineItems();
        foreach ($discountLineItems as $discountLineItem) {
            $discounts = $discountLineItem->getDiscounts();
            foreach ($discounts as $discount) {
                $discountAmount = $discount->calculate($discountLineItem);
                $discountLineItem->addDiscountInformation(new DiscountInformation($discount, $discountAmount));
                $discountLineItem->setSubtotal(
                    $this->getSubtotalWithDiscount($discountLineItem->getSubtotal(), $discountAmount)
                );
            }
        }
    }

    protected function processTotalDiscounts(DiscountContextInterface $discountContext)
    {
        $discounts = $discountContext->getSubtotalDiscounts();
        foreach ($discounts as $discount) {
            $discountAmount = $discount->calculate($discountContext);
            $discountContext->addSubtotalDiscountInformation(new DiscountInformation($discount, $discountAmount));
            $discountContext->setSubtotal(
                $this->getSubtotalWithDiscount($discountContext->getSubtotal(), $discountAmount)
            );
            $this->allocateTotalDiscountAmountToLineItems($discountContext, $discountAmount);
        }
    }

    protected function processShippingDiscounts(DiscountContextInterface $discountContext)
    {
        $multiShippingDiscountAmount = 0.0;
        $notMultiShippingDiscounts = [];
        $multiShippingDiscountInformation = [];

        $discounts = $discountContext->getShippingDiscounts();
        foreach ($discounts as $discount) {
            $promotion = $discount->getPromotion();
            if ($promotion instanceof MultiShippingPromotionData) {
                $discountAmount = $discount->calculate($promotion);
                $multiShippingDiscountAmount += $discountAmount;
                $multiShippingDiscountInformation[] = new DiscountInformation($discount, $discountAmount);
            } else {
                $notMultiShippingDiscounts[] = $discount;
            }
        }

        foreach ($notMultiShippingDiscounts as $discount) {
            $discountAmount = $discount->calculate($discountContext);
            $discountContext->addShippingDiscountInformation(new DiscountInformation($discount, $discountAmount));
            $discountContext->setShippingCost(
                $this->getSubtotalWithDiscount($discountContext->getShippingCost(), $discountAmount)
            );
        }

        if ($multiShippingDiscountAmount > 0.0) {
            foreach ($multiShippingDiscountInformation as $discountInformation) {
                $discountContext->addShippingDiscountInformation($discountInformation);
            }
            $discountContext->setShippingCost(
                $this->getSubtotalWithDiscount($discountContext->getShippingCost(), $multiShippingDiscountAmount)
            );
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
        $lastLineItemDiscountAmount = $discountAmount;

        /** @var DiscountLineItemInterface $discountLineItem */
        foreach ($lineItems as $discountLineItem) {
            $lineItemDiscountAmount = $this->calculateLineItemDiscountAmount(
                $discountContext,
                $discountLineItem,
                $discountAmount
            );

            $discountLineItem->setSubtotalAfterDiscounts(
                $this->getSubtotalWithDiscount($discountLineItem->getSubtotalAfterDiscounts(), $lineItemDiscountAmount)
            );

            $lastLineItemDiscountAmount -= $lineItemDiscountAmount;
        }

        if ($lastLineItem instanceof DiscountLineItemInterface) {
            $lastLineItem->setSubtotalAfterDiscounts(
                $this->getSubtotalWithDiscount($lastLineItem->getSubtotalAfterDiscounts(), $lastLineItemDiscountAmount)
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

        return ($discountLineItem->getSubtotalAfterDiscounts() * $discountAmount) / $subtotal;
    }
}
