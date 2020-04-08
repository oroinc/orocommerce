<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;

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
     * @var ResolvedProductVisibilityProvider|null
     */
    private $resolvedProductVisibilityProvider;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param ResolvedProductVisibilityProvider|null $resolvedProductVisibilityProvider
     */
    public function setResolvedProductVisibilityProvider(
        ?ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider
    ): void {
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
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

        $cleared = [];
        foreach ($entities as $entity) {
            $product = null;
            if ($entity instanceof Product) {
                $product = $entity;
            } elseif ($entity instanceof BaseProductVisibilityResolved) {
                $product = $entity->getProduct();
            }

            if ($product) {
                $productId = $product->getId();
                if (!isset($cleared[$productId])) {
                    if ($this->resolvedProductVisibilityProvider) {
                        $this->resolvedProductVisibilityProvider->clearCache($productId);
                    } else {
                        $this->cache->delete(Product::class . '_' . $productId);
                    }
                    $cleared[$productId] = true;
                }
            }
        }
    }
}
