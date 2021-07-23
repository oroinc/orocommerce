<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

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
     * @param object $sourceEntity
     * @param DiscountContextInterface $context
     * @return DiscountInterface[]
     */
    public function getDiscounts($sourceEntity, DiscountContextInterface $context): array
    {
        $discounts = [];

        foreach ($this->promotionProvider->getPromotions($sourceEntity) as $promotion) {
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
