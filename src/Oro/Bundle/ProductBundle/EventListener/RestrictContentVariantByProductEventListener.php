<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntityEvent;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Restrict content variant query builder by a given product.
 */
class RestrictContentVariantByProductEventListener
{
    public function applyRestriction(RestrictContentVariantByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Product) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq(
                    QueryBuilderUtil::getField($event->getVariantAlias(), 'product_page_product'),
                    ':product'
                ))
                ->setParameter('product', $entity);
        }
    }
}
