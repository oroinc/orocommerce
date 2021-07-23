<?php

namespace Oro\Bundle\PromotionBundle\Executor;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use Oro\Bundle\OrderBundle\Entity\Order;
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
     * @var Cache|null
     */
    private $cache;

    /**
     * @var ObjectCacheKeyGenerator|null
     */
    private $objectCacheKeyGenerator;

    public function __construct(
        DiscountContextConverterInterface $discountContextConverter,
        StrategyProvider $discountStrategyProvider,
        PromotionDiscountsProviderInterface $promotionDiscountsProvider
    ) {
        $this->discountContextConverter = $discountContextConverter;
        $this->discountStrategyProvider = $discountStrategyProvider;
        $this->promotionDiscountsProvider = $promotionDiscountsProvider;
    }

    public function setCache(Cache $cache): void
    {
        $this->cache = $cache;
    }

    public function setObjectCacheKeyGenerator(ObjectCacheKeyGenerator $objectCacheKeyGenerator): void
    {
        $this->objectCacheKeyGenerator = $objectCacheKeyGenerator;
    }

    /**
     * @param object $sourceEntity
     * @return DiscountContextInterface
     */
    public function execute($sourceEntity): DiscountContextInterface
    {
        if ($this->cache && $this->objectCacheKeyGenerator) {
            $cacheKey = $this->objectCacheKeyGenerator->generate($sourceEntity, 'promotion');
            if ($this->cache->contains($cacheKey)) {
                return $this->cache->fetch($cacheKey);
            }
        }

        $discountContext = $this->discountContextConverter->convert($sourceEntity);
        $discounts = $this->promotionDiscountsProvider->getDiscounts($sourceEntity, $discountContext);

        if ($discounts) {
            $strategy = $this->discountStrategyProvider->getActiveStrategy();
            $discountContext = $strategy->process($discountContext, $discounts);
        }

        if ($this->cache && $this->objectCacheKeyGenerator) {
            $this->cache->save($cacheKey, $discountContext);
        }

        return $discountContext;
    }

    /**
     * @param object $sourceEntity
     * @return bool
     */
    public function supports($sourceEntity)
    {
        if ($sourceEntity instanceof Order && $sourceEntity->getDisablePromotions()) {
            return false;
        }

        return $this->discountContextConverter->supports($sourceEntity);
    }
}
