<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;

/**
 * Decorates PromotionDiscountProvider to disable discounts with promotions for which disabled applied discount exists.
 */
class DisabledPromotionDiscountProviderDecorator implements PromotionDiscountsProviderInterface
{
    public function __construct(
        private PromotionDiscountsProviderInterface $promotionDiscountProvider,
        private PromotionAwareEntityHelper $promotionAwareHelper,
    ) {
    }

    /**
     * @param object $sourceEntity
     * @param DiscountContextInterface $context
     * @return DiscountInterface[]
     */
    public function getDiscounts($sourceEntity, DiscountContextInterface $context): array
    {
        $discounts = $this->promotionDiscountProvider->getDiscounts($sourceEntity, $context);

        if ($this->promotionAwareHelper->isPromotionAware($sourceEntity) && !empty($discounts)) {
            $disabledPromotions = [];

            foreach ($sourceEntity->getAppliedPromotions() as $appliedPromotion) {
                if (!$appliedPromotion->isActive()) {
                    $disabledPromotions[$appliedPromotion->getSourcePromotionId()] = true;
                }
            }

            for ($index = 0, $discountsLength = count($discounts); $index < $discountsLength; ++$index) {
                $discount = $discounts[$index];

                if (isset($disabledPromotions[$discount->getPromotion()->getId()])) {
                    $discounts[$index] = new DisabledDiscountDecorator($discount);
                }
            }
        }

        return $discounts;
    }
}
