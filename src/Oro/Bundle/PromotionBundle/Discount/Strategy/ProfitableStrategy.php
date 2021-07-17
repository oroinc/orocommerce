<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

/**
 * Responsible for applying discounts
 */
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
    public function process(DiscountContextInterface $discountContext, array $discounts): DiscountContextInterface
    {
        $appliedDiscountContext = $this->getContextCopyWithAppliedDiscounts($discountContext, $discounts);

        $this->applyMaxProductDiscount($discountContext, $appliedDiscountContext);
        $this->applyMaxShippingDiscount($discountContext, $appliedDiscountContext);

        return $discountContext;
    }

    private function calculateDiscount(DiscountInterface $discount, DiscountContextInterface $discountContext)
    {
        $discount->apply($discountContext);

        $this->processLineItemDiscounts($discountContext);
        $this->processTotalDiscounts($discountContext);
        $this->processShippingDiscounts($discountContext);
    }

    private function applyMaxProductDiscount(
        DiscountContextInterface $discountContext,
        DiscountContextInterface $appliedDiscountContext
    ) {
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

    private function applyMaxShippingDiscount(
        DiscountContextInterface $discountContext,
        DiscountContextInterface $appliedDiscountContext
    ) {
        $maxShippingDiscount = $this->getMaxDiscount($discountContext, $appliedDiscountContext->getShippingDiscounts());

        if ($maxShippingDiscount) {
            $maxShippingDiscount->apply($discountContext);
            $this->processShippingDiscounts($discountContext);
        }
    }

    /**
     * @param DiscountContextInterface $discountContext
     * @param array|DiscountInterface[] $discounts
     * @return DiscountInterface|null
     */
    private function getMaxDiscount(DiscountContextInterface $discountContext, array $discounts)
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
     * @param DiscountContextInterface $discountContext
     * @param array|DiscountInterface[] $discounts
     * @return DiscountContextInterface
     */
    private function getContextCopyWithAppliedDiscounts(DiscountContextInterface $discountContext, array $discounts)
    {
        $appliedDiscountContext = $this->cloneContext($discountContext);
        foreach ($discounts as $discount) {
            $discount->apply($appliedDiscountContext);
        }

        return $appliedDiscountContext;
    }

    private function cloneContext(DiscountContextInterface $discountContext): DiscountContextInterface
    {
        if (\method_exists($discountContext, '__clone')) {
            /** @var \Oro\Bundle\PromotionBundle\Discount\DiscountContext $discountContext */
            return clone $discountContext;
        }

        return unserialize(serialize($discountContext));
    }
}
