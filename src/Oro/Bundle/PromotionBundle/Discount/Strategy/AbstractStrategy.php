<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;

abstract class AbstractStrategy implements StrategyInterface
{
    /**
     * @param DiscountContextInterface $discountContext
     */
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

    /**
     * @param DiscountContextInterface $discountContext
     */
    protected function processTotalDiscounts(DiscountContextInterface $discountContext)
    {
        foreach ($discountContext->getSubtotalDiscounts() as $discount) {
            $discountAmount = $discount->calculate($discountContext);
            $discountContext->addSubtotalDiscountInformation(new DiscountInformation($discount, $discountAmount));

            $subtotal = $this->getSubtotalWithDiscount($discountContext->getSubtotal(), $discountAmount);
            $discountContext->setSubtotal($subtotal);
        }
    }

    /**
     * @param DiscountContextInterface $discountContext
     */
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
}
