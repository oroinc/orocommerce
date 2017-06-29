<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;

class OrdersAppliedDiscountsProvider
{
    const CACHE_PREFIX = 'oro_promotion.provider.applied_discounts_provider:';

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param Cache $cache
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(Cache $cache, DoctrineHelper $doctrineHelper)
    {
        $this->cache = $cache;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Returns applied orders discounts by order id
     *
     * @param int $orderId
     * @return AppliedDiscount[]
     */
    public function getOrderDiscounts(int $orderId): array
    {
        $cacheKey = $this->getCacheKey($orderId);

        if ($this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }

        $discounts = $this->doctrineHelper->getEntityRepositoryForClass(AppliedDiscount::class)->findBy([
            'order' => $orderId
        ]);
        $this->cache->save($cacheKey, $discounts);

        return $discounts;
    }

    /**
     * @param int $orderId
     * @return string
     */
    protected function getCacheKey(int $orderId): string
    {
        return self::CACHE_PREFIX . $orderId;
    }
}
