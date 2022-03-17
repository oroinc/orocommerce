<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Clears product availability cache on change related entities
 */
class ProductAvailabilityCacheListener
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();
        $entities = array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $unitOfWork->getScheduledEntityUpdates(),
            $unitOfWork->getScheduledEntityDeletions()
        );
        $productsId = [];
        foreach ($entities as $entity) {
            if ($entity instanceof Product
                && null !== $entity->getId()
            ) {
                $productsId[] = (string) $entity->getId();
            }
        }
        $this->cache->deleteItems(array_unique($productsId));
    }
}
