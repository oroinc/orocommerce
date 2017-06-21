<?php

namespace Oro\Bundle\PromotionBundle\Executor;

use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
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
     * @var StrategyInterface
     */
    private $discountStrategy;

    /**
     * @param PromotionProvider $promotionProvider
     * @param DiscountContextConverterInterface $discountContextConverter
     * @param DiscountFactory $discountFactory
     * @param StrategyInterface $discountStrategy
     */
    public function __construct(
        PromotionProvider $promotionProvider,
        DiscountContextConverterInterface $discountContextConverter,
        DiscountFactory $discountFactory,
        StrategyInterface $discountStrategy
    ) {
        $this->promotionProvider = $promotionProvider;
        $this->discountContextConverter = $discountContextConverter;
        $this->discountFactory = $discountFactory;
        $this->discountStrategy = $discountStrategy;
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
            $discounts[] = $this->discountFactory->create($promotion->getDiscountConfiguration());
        }
        if (!$discounts) {
            return $discountContext;
        }

        return $this->discountStrategy->process($discountContext, $discounts);
    }
}
