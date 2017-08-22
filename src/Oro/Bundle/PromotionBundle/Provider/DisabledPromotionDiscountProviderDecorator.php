<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscountsAwareInterface;

/**
 * Decorates PromotionDiscountProvider to disable discounts with promotions for which disabled applied discount exists.
 */
class DisabledPromotionDiscountProviderDecorator implements PromotionDiscountsProviderInterface
{
    /**
     * @var PromotionDiscountsProviderInterface
     */
    private $promotionDiscountProvider;

    /**
     * @param PromotionDiscountsProviderInterface $promotionDiscountsProvider
     */
    public function __construct(PromotionDiscountsProviderInterface $promotionDiscountsProvider)
    {
        $this->promotionDiscountProvider = $promotionDiscountsProvider;
    }

    /**
     * @param object $sourceEntity
     * @param DiscountContext $context
     * @return DiscountInterface[]
     */
    public function getDiscounts($sourceEntity, DiscountContext $context): array
    {
        $discounts = $this->promotionDiscountProvider->getDiscounts($sourceEntity, $context);

        if ($sourceEntity instanceof AppliedDiscountsAwareInterface && !empty($discounts)) {
            $disabledPromotions = [];

            foreach ($sourceEntity->getAppliedDiscounts() as $appliedDiscount) {
                if (!$appliedDiscount->isEnabled()) {
                    $disabledPromotions[$appliedDiscount->getSourcePromotionId()] = true;
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
