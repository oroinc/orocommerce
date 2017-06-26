<?php

namespace Oro\Bundle\PromotionBundle\Executor;

use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;

class PromotionExecutor
{
    /**
     * @var PromotionProvider
     */
    private $promotionProvider;

    /**
     * @var DiscountContextConverterInterface
     */
    private $discountContextConverter;

    /**
     * @var DiscountFactory
     */
    private $discountFactory;

    /**
     * @var StrategyProvider
     */
    private $discountStrategyProvider;

    /**
     * @var MatchingProductsProvider
     */
    private $matchingProductsProvider;

    /**
     * @param PromotionProvider $promotionProvider
     * @param DiscountContextConverterInterface $discountContextConverter
     * @param DiscountFactory $discountFactory
     * @param StrategyProvider $discountStrategyProvider
     * @param MatchingProductsProvider $matchingProductsProvider
     */
    public function __construct(
        PromotionProvider $promotionProvider,
        DiscountContextConverterInterface $discountContextConverter,
        DiscountFactory $discountFactory,
        StrategyProvider $discountStrategyProvider,
        MatchingProductsProvider $matchingProductsProvider
    ) {
        $this->promotionProvider = $promotionProvider;
        $this->discountContextConverter = $discountContextConverter;
        $this->discountFactory = $discountFactory;
        $this->discountStrategyProvider = $discountStrategyProvider;
        $this->matchingProductsProvider = $matchingProductsProvider;
    }

    /**
     * @param object $sourceEntity
     * @return DiscountContext
     */
    public function execute($sourceEntity): DiscountContext
    {
        $discountContext = $this->discountContextConverter->convert($sourceEntity);
        $discounts = [];
        foreach ($this->promotionProvider->getPromotions($sourceEntity) as $promotion) {
            $discount = $this->discountFactory->create($promotion->getDiscountConfiguration());
            $discount->setMatchingProducts(
                $this->matchingProductsProvider->getMatchingProducts(
                    $promotion->getProductsSegment(),
                    $discountContext->getLineItems()
                )
            );
            $discounts[] = $discount;
        }
        if (!$discounts) {
            return $discountContext;
        }

        $strategy = $this->discountStrategyProvider->getActiveStrategy();

        return $strategy->process($discountContext, $discounts);
    }

    /**
     * @param object $sourceEntity
     * @return bool
     */
    public function supports($sourceEntity)
    {
        return $this->discountContextConverter->supports($sourceEntity);
    }
}
