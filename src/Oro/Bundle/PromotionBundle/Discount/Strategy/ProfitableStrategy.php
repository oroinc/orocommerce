<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

class ProfitableStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.promotion.discount.strategy.profitable.label';
    }

    /**
     * {@inheritdoc}
     */
    public function process(DiscountContext $discountContext, array $discounts): DiscountContext
    {
        $maxDiscountAmount = 0.0;
        $maxDiscount = null;

        foreach ($discounts as $discount) {
            $calculateContext = clone $discountContext;
            $this->calculateDiscount($discount, $calculateContext);

            if ($calculateContext->getTotalDiscountAmount() > $maxDiscountAmount) {
                $maxDiscount = $discount;
            }
        }

        if ($maxDiscount) {
            $this->calculateDiscount($maxDiscount, $discountContext);
        }

        return $discountContext;
    }

    /**
     * @param DiscountInterface $discount
     * @param DiscountContext $discountContext
     */
    private function calculateDiscount(DiscountInterface $discount, DiscountContext $discountContext)
    {
        $discount->apply($discountContext);

        $this->processLineItemDiscounts($discountContext);
        $this->processTotalDiscounts($discountContext);
        $this->processShippingDiscounts($discountContext);
    }
}
