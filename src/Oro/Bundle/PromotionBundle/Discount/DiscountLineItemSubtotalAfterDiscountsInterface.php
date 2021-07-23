<?php

namespace Oro\Bundle\PromotionBundle\Discount;

/**
 * Interface for line item which supports specifying subtotal after discounts are applied
 */
interface DiscountLineItemSubtotalAfterDiscountsInterface
{
    public function getSubtotalAfterDiscounts(): float;

    /**
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotalAfterDiscounts(float $subtotal): self;
}
