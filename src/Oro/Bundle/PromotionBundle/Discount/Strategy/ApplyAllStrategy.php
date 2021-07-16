<?php

namespace Oro\Bundle\PromotionBundle\Discount\Strategy;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;

class ApplyAllStrategy extends AbstractStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.promotion.discount.strategy.apply_all.label';
    }

    /**
     * {@inheritdoc}
     */
    public function process(DiscountContextInterface $discountContext, array $discounts): DiscountContextInterface
    {
        foreach ($discounts as $discount) {
            $discount->apply($discountContext);
        }

        $this->processLineItemDiscounts($discountContext);
        $this->updateContextSubtotal($discountContext);

        $this->processTotalDiscounts($discountContext);
        $this->processShippingDiscounts($discountContext);

        return $discountContext;
    }

    private function updateContextSubtotal(DiscountContextInterface $discountContext)
    {
        $lineItemsTotalDiscount = 0.0;
        foreach ($discountContext->getLineItems() as $lineItem) {
            foreach ($lineItem->getDiscountsInformation() as $discountInformation) {
                $lineItemsTotalDiscount += $discountInformation->getDiscountAmount();
            }
        }

        $discountContext->setSubtotal(
            $this->getSubtotalWithDiscount($discountContext->getSubtotal(), $lineItemsTotalDiscount)
        );
    }
}
