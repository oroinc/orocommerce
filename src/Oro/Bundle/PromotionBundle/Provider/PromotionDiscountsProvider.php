<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;

/**
 * Returns discounts for a given source entity and configures them with matching items.
 */
class PromotionDiscountsProvider implements PromotionDiscountsProviderInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private PromotionProvider $promotionProvider,
        private DiscountFactory $discountFactory,
        private MatchingProductsProviderInterface $matchingProductsProvider
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscounts(object $sourceEntity, DiscountContextInterface $context): array
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        $discounts = [];
        $promotions = $this->promotionProvider->getPromotions($sourceEntity);
        foreach ($promotions as $promotion) {
            $discounts[] = $this->createDiscount($promotion, $context);
        }

        return $discounts;
    }

    private function createDiscount(
        PromotionDataInterface $promotion,
        DiscountContextInterface $context
    ): DiscountInterface {
        $discount = $this->discountFactory->create($promotion->getDiscountConfiguration(), $promotion);
        $discount->setMatchingProducts($this->getMatchingProducts($promotion, $context));

        return $discount;
    }

    private function getMatchingProducts(PromotionDataInterface $promotion, DiscountContextInterface $context): array
    {
        return $this->matchingProductsProvider->getMatchingProducts(
            $promotion->getProductsSegment(),
            $promotion instanceof MultiShippingPromotionData ? $promotion->getLineItems() : $context->getLineItems(),
            $promotion instanceof Promotion ? $promotion->getOrganization() : null
        );
    }
}
