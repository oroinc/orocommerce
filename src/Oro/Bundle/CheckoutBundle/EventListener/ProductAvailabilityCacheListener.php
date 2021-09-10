<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Clears product availability cache on change related entities
 */
class ProductAvailabilityCacheListener
{
    /**
     * @var CacheProvider
     */
    private $cache;

    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    public function onFlush(OnFlushEventArgs $args)
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
                $productsId[] = $entity->getId();
            }
        }
        foreach (array_unique($productsId) as $productId) {
            $this->cache->delete($productId);
        }
    }
}
