<?php

namespace Oro\Bundle\PromotionBundle\Executor;

use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;

/**
 * This class fills context with discounts' information for a given source entity using currently selected strategy.
 */
class PromotionExecutor
{
    /**
     * @var DiscountContextConverterInterface
     */
    private $discountContextConverter;

    /**
     * @var StrategyProvider
     */
    private $discountStrategyProvider;

    /**
     * @var PromotionDiscountsProviderInterface
     */
    private $promotionDiscountsProvider;

    /**
     * @param DiscountContextConverterInterface $discountContextConverter
     * @param StrategyProvider $discountStrategyProvider
     * @param PromotionDiscountsProviderInterface $promotionDiscountsProvider
     */
    public function __construct(
        DiscountContextConverterInterface $discountContextConverter,
        StrategyProvider $discountStrategyProvider,
        PromotionDiscountsProviderInterface $promotionDiscountsProvider
    ) {
        $this->discountContextConverter = $discountContextConverter;
        $this->discountStrategyProvider = $discountStrategyProvider;
        $this->promotionDiscountsProvider = $promotionDiscountsProvider;
    }

    /**
     * @param object $sourceEntity
     * @return DiscountContextInterface
     */
    public function execute($sourceEntity): DiscountContextInterface
    {
        $discountContext = $this->discountContextConverter->convert($sourceEntity);
        $discounts = $this->promotionDiscountsProvider->getDiscounts($sourceEntity, $discountContext);

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
