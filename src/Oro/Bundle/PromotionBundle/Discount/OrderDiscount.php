<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

/**
 * Applies discounts to the order subtotal.
 *
 * Calculates and applies a discount to the entire order subtotal,
 * supporting both fixed amount and percentage discount types.
 */
class OrderDiscount extends AbstractDiscount
{
    #[\Override]
    public function apply(DiscountContextInterface $discountContext)
    {
        $discountContext->addSubtotalDiscount($this);
    }

    #[\Override]
    public function calculate($entity): float
    {
        if (!$entity instanceof SubtotalAwareInterface) {
            return 0.0;
        }

        return $this->calculateDiscountAmount($entity->getSubtotal());
    }
}
