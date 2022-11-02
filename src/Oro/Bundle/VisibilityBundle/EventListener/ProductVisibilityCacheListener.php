<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;

/**
 * This listener clear product visibility cache on change related entities
 */
class ProductVisibilityCacheListener
{
    /** @var ResolvedProductVisibilityProvider */
    private $resolvedProductVisibilityProvider;

    public function __construct(ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider)
    {
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
    }

    public function onFlush(OnFlushEventArgs $args): void
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
                    $this->resolvedProductVisibilityProvider->clearCache($productId);
                    $cleared[$productId] = true;
                }
            }
        }
    }
}
