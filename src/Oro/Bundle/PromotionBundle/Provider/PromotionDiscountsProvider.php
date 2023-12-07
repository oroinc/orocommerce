<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;

/**
 * Returns discounts for a given source entity and configures them with matching items.
 */
class PromotionDiscountsProvider implements PromotionDiscountsProviderInterface
{
    /**
     * @var PromotionProvider
     */
    private $promotionProvider;

    /**
     * @var DiscountFactory
     */
    private $discountFactory;

    /**
     * @var MatchingProductsProvider
     */
    private $matchingProductsProvider;

    public function __construct(
        PromotionProvider $promotionProvider,
        DiscountFactory $discountFactory,
        MatchingProductsProvider $matchingProductsProvider
    ) {
        $this->promotionProvider = $promotionProvider;
        $this->discountFactory = $discountFactory;
        $this->matchingProductsProvider = $matchingProductsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscounts($sourceEntity, DiscountContextInterface $context): array
    {
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
            $promotion instanceof MultiShippingPromotionData ? $promotion->getLineItems() : $context->getLineItems()
        );
    }
}
