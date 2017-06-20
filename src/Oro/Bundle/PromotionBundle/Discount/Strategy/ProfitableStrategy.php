<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
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

            $discountAmount = $this->getTotalDiscountAmount($calculateContext);
            if ($discountAmount > $maxDiscountAmount) {
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

    /**
     * @param DiscountContext $discountContext
     * @return float
     */
    private function getTotalDiscountAmount(DiscountContext $discountContext): float
    {
        $value = 0.0;
        foreach ($discountContext->getLineItems() as $lineItem) {
            $value += $this->getDiscountInformationSum($lineItem->getDiscountsInformation());
        }
        $value += $this->getDiscountInformationSum($discountContext->getSubtotalDiscountsInformation());
        $value += $this->getDiscountInformationSum($discountContext->getShippingDiscountsInformation());

        return $value;
    }

    /**
     * @param array|DiscountInformation[] $discountsInformation
     * @return float
     */
    private function getDiscountInformationSum(array $discountsInformation): float
    {
        $value = 0.0;
        foreach ($discountsInformation as $discountInformation) {
            $value += $discountInformation->getDiscountAmount();
        }

        return $value;
    }
}
