<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class LineItemsDiscount extends ShippingAwareDiscount
{
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return 'Line Items Discount ' . parent::__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(DiscountContext $discountContext)
    {
        parent::apply($discountContext);

        // TODO: Implement apply() method.
    }

    /**
     * {@inheritdoc}
     */
    public function calculate($entity): float
    {
        if (!$entity instanceof SubtotalAwareInterface) {
            return 0.0;
        }
        // TODO: Implement calculate() method.
        // TODO: Use BigDecimal

        return $entity->getSubtotal() * $this->getDiscountValue();
    }
}
