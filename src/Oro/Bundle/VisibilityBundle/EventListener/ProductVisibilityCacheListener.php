<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

/**
 * This listener clear product visibility cache on change related entities
 */
class ProductVisibilityCacheListener
{
    /**
     * @var CacheProvider
     */
    private $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();

        $entities = array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $unitOfWork->getScheduledEntityUpdates(),
            $unitOfWork->getScheduledEntityDeletions()
        );

        $productIds = [];
        foreach ($entities as $entity) {
            if ($entity instanceof Product) {
                $productIds[] = $entity->getId();
            } elseif ($entity instanceof BaseProductVisibilityResolved) {
                $productIds[] = $entity->getProduct()->getId();
            }
        }

        foreach (array_unique($productIds) as $productId) {
            $this->cache->delete(Product::class . '_' . $productId);
        }
    }
}
