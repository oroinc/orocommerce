<?php

namespace Oro\Bundle\PromotionBundle\Executor;

use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Fills context with discounts' information for a given source entity using currently selected strategy.
 */
class PromotionExecutor
{
    private DiscountContextConverterInterface $discountContextConverter;
    private StrategyProvider $discountStrategyProvider;
    private PromotionDiscountsProviderInterface $promotionDiscountsProvider;
    private ?CacheInterface $cache = null;
    private ?ObjectCacheKeyGenerator $objectCacheKeyGenerator = null;

    public function __construct(
        DiscountContextConverterInterface $discountContextConverter,
        StrategyProvider $discountStrategyProvider,
        PromotionDiscountsProviderInterface $promotionDiscountsProvider
    ) {
        $this->discountContextConverter = $discountContextConverter;
        $this->discountStrategyProvider = $discountStrategyProvider;
        $this->promotionDiscountsProvider = $promotionDiscountsProvider;
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function setObjectCacheKeyGenerator(ObjectCacheKeyGenerator $objectCacheKeyGenerator): void
    {
        $this->objectCacheKeyGenerator = $objectCacheKeyGenerator;
    }

    public function execute(object $sourceEntity): DiscountContextInterface
    {
        if (null !== $this->cache && null !== $this->objectCacheKeyGenerator) {
            return $this->cache->get(
                $this->objectCacheKeyGenerator->generate($sourceEntity, 'promotion'),
                function () use ($sourceEntity) {
                    return $this->calculateDiscountContext($sourceEntity);
                }
            );
        }

        return $this->calculateDiscountContext($sourceEntity);
    }

    public function supports(object $sourceEntity): bool
    {
        return $this->discountContextConverter->supports($sourceEntity);
    }

    private function calculateDiscountContext(object $sourceEntity): DiscountContextInterface
    {
        $discountContext = $this->discountContextConverter->convert($sourceEntity);
        $discounts = $this->promotionDiscountsProvider->getDiscounts($sourceEntity, $discountContext);
        if ($discounts) {
            $strategy = $this->discountStrategyProvider->getActiveStrategy();
            if (null !== $strategy) {
                $discountContext = $strategy->process($discountContext, $discounts);
            }
        }

        return $discountContext;
    }
}
