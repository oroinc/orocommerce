<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;

/**
 * Returns discounts for a given source entity and configures them with matching items.
 */
class PromotionDiscountsProvider implements PromotionDiscountsProviderInterface
{
    public function __construct(
        private PromotionProvider $promotionProvider,
        private DiscountFactory $discountFactory,
        private MatchingProductsProvider $matchingProductsProvider
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscounts(object $sourceEntity, DiscountContextInterface $context): array
    {
        $discounts = [];
        $promotions = $this->promotionProvider->getPromotions($sourceEntity);
        foreach ($promotions as $promotion) {
            $discount = $this->discountFactory->create($promotion->getDiscountConfiguration(), $promotion);
            $discount->setMatchingProducts(
                $this->matchingProductsProvider->getMatchingProducts(
                    $promotion->getProductsSegment(),
                    $context->getLineItems()
                )
            );
            $discounts[] = $discount;
        }

        return $discounts;
    }
}
