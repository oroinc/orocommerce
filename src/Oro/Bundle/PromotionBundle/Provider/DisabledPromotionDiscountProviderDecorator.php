<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;

/**
 * Disables discounts with promotions for which disabled applied discount exists.
 */
class DisabledPromotionDiscountProviderDecorator implements PromotionDiscountsProviderInterface
{
    public function __construct(
        private PromotionDiscountsProviderInterface $baseDiscountsProvider,
        private PromotionAwareEntityHelper $promotionAwareHelper,
    ) {
    }

    #[\Override]
    public function getDiscounts(object $sourceEntity, DiscountContextInterface $context): array
    {
        $discounts = $this->baseDiscountsProvider->getDiscounts($sourceEntity, $context);
        if (!empty($discounts) && $this->promotionAwareHelper->isPromotionAware($sourceEntity)) {
            $appliedDisabledPromotions = $this->getAppliedDisabledPromotions($sourceEntity);
            if ($appliedDisabledPromotions) {
                foreach ($discounts as $index => $discount) {
                    if (isset($appliedDisabledPromotions[$discount->getPromotion()->getId()])) {
                        $discounts[$index] = new DisabledDiscountDecorator($discount);
                    }
                }
            }
        }

        return $discounts;
    }

    private function getAppliedDisabledPromotions(object $sourceEntity): array
    {
        $appliedDisabledPromotions = [];
        $appliedPromotions = $sourceEntity->getAppliedPromotions();
        /** @var AppliedPromotion $appliedPromotion */
        foreach ($appliedPromotions as $appliedPromotion) {
            if (!$appliedPromotion->isActive()) {
                $appliedDisabledPromotions[$appliedPromotion->getSourcePromotionId()] = true;
            }
        }

        return $appliedDisabledPromotions;
    }
}
