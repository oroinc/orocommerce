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
        $appliedDiscountContext = $this->getContextCopyWithAppliedDiscounts($discountContext, $discounts);

        $this->applyMaxProductDiscount($discountContext, $appliedDiscountContext);
        $this->applyMaxShippingDiscount($discountContext, $appliedDiscountContext);

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
     * @param DiscountContext $appliedDiscountContext
     */
    private function applyMaxProductDiscount(DiscountContext $discountContext, DiscountContext $appliedDiscountContext)
    {
        $maxDiscount = $this->getMaxDiscount(
            $discountContext,
            array_merge(
                $appliedDiscountContext->getLineItemDiscounts(),
                $appliedDiscountContext->getSubtotalDiscounts()
            )
        );

        if ($maxDiscount) {
            $maxDiscount->apply($discountContext);
            $this->processLineItemDiscounts($discountContext);
            $this->processTotalDiscounts($discountContext);
        }
    }

    /**
     * @param DiscountContext $discountContext
     * @param DiscountContext $appliedDiscountContext
     */
    private function applyMaxShippingDiscount(DiscountContext $discountContext, DiscountContext $appliedDiscountContext)
    {
        $maxShippingDiscount = $this->getMaxDiscount($discountContext, $appliedDiscountContext->getShippingDiscounts());

        if ($maxShippingDiscount) {
            $maxShippingDiscount->apply($discountContext);
            $this->processShippingDiscounts($discountContext);
        }
    }

    /**
     * @param DiscountContext $discountContext
     * @param array|DiscountInterface[] $discounts
     * @return DiscountInterface|null
     */
    private function getMaxDiscount(DiscountContext $discountContext, array $discounts)
    {
        $maxDiscountAmount = 0.0;
        $maxDiscount = null;
        foreach ($discounts as $discount) {
            $calculateContext = $this->cloneContext($discountContext);
            $this->calculateDiscount($discount, $calculateContext);

            $totalDiscountAmount = $calculateContext->getTotalDiscountAmount();
            if ($totalDiscountAmount > $maxDiscountAmount) {
                $maxDiscount = $discount;
                $maxDiscountAmount = $totalDiscountAmount;
            }
        }

        return $maxDiscount;
    }

    /**
     * @param DiscountContext $discountContext
     * @param array|DiscountInterface[] $discounts
     * @return DiscountContext
     */
    private function getContextCopyWithAppliedDiscounts(DiscountContext $discountContext, array $discounts)
    {
        $appliedDiscountContext = $this->cloneContext($discountContext);
        foreach ($discounts as $discount) {
            $discount->apply($appliedDiscountContext);
        }

        return $appliedDiscountContext;
    }

    /**
     * @param DiscountContext $discountContext
     * @return DiscountContext
     */
    private function cloneContext(DiscountContext $discountContext): DiscountContext
    {
        return unserialize(serialize($discountContext));
    }
}
