<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class OrderDiscount extends ShippingAwareDiscount
{
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return 'Order Discount ' . parent::__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContext $discountContext)
    {
        parent::apply($discountContext);

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
