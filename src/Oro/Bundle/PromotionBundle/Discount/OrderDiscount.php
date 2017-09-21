<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class OrderDiscount extends AbstractDiscount
{
    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContextInterface $discountContext)
    {
        $discountContext->addSubtotalDiscount($this);
    }

    /**
     * {@inheritdoc}
     */
    public function calculate($entity): float
    {
        if (!$entity instanceof SubtotalAwareInterface) {
            return 0.0;
        }

        return $this->calculateDiscountAmount($entity->getSubtotal());
    }
}
