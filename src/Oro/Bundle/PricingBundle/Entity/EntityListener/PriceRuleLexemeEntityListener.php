<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Psr\Cache\CacheItemPoolInterface;

/**
 * This listener will invalidate doctrine result cache for queries for PriceRuleLexeme entity
 * and price list dependencies cache.
 * Caches will be invalidated on persist, update and remove doctrine events.
 */
class PriceRuleLexemeEntityListener
{
    private CacheItemPoolInterface $cache;

    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function postPersist(PriceRuleLexeme $priceRuleLexeme, LifecycleEventArgs $event)
    {
        $this->invalidateCaches($event->getObjectManager());
    }

    public function postUpdate(PriceRuleLexeme $priceRuleLexeme, LifecycleEventArgs $event)
    {
        $this->invalidateCaches($event->getObjectManager());
    }

    public function postRemove(PriceRuleLexeme $priceRuleLexeme, LifecycleEventArgs $event)
    {
        $this->invalidateCaches($event->getObjectManager());
    }

    protected function invalidateRepositoryCache(EntityManager $entityManager)
    {
        $entityManager->getRepository(PriceRuleLexeme::class)->invalidateCache();
    }

    private function invalidateCaches(EntityManager $entityManager)
    {
        $this->invalidateRepositoryCache($entityManager);
        $this->cache->clear();
    }
}
