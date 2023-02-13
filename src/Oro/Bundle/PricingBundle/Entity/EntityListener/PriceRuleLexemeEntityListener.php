<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;

/**
 * This listener will invalidate doctrine result cache for queries for PriceRuleLexeme entity.
 * Cache will be invalidated on persist, update and remove doctrine events.
 */
class PriceRuleLexemeEntityListener
{
    public function postPersist(PriceRuleLexeme $priceRuleLexeme, LifecycleEventArgs $event)
    {
        $this->invalidateRepositoryCache($event->getObjectManager());
    }

    public function postUpdate(PriceRuleLexeme $priceRuleLexeme, LifecycleEventArgs $event)
    {
        $this->invalidateRepositoryCache($event->getObjectManager());
    }

    public function postRemove(PriceRuleLexeme $priceRuleLexeme, LifecycleEventArgs $event)
    {
        $this->invalidateRepositoryCache($event->getObjectManager());
    }

    protected function invalidateRepositoryCache(EntityManager $entityManager)
    {
        $entityManager->getRepository(PriceRuleLexeme::class)->invalidateCache();
    }
}
