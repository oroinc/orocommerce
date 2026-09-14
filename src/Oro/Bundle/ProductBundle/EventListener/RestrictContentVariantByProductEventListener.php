<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntitiesEvent;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntityEvent;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Restrict content variant query builder by a given product or by a set of products.
 */
class RestrictContentVariantByProductEventListener
{
    private const string ASSOCIATION = 'product_page_product';

    public function applyRestriction(RestrictContentVariantByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Product) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq(
                    QueryBuilderUtil::getField($event->getVariantAlias(), self::ASSOCIATION),
                    ':product'
                ))
                ->setParameter('product', $entity);
        }
    }

    public function applyRestrictionForEntities(RestrictContentVariantByEntitiesEvent $event): void
    {
        if (!is_a($event->getEntityClass(), Product::class, true)) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();
        $field = QueryBuilderUtil::getField($event->getVariantAlias(), self::ASSOCIATION);
        $queryBuilder
            ->addSelect(QueryBuilderUtil::sprintf('IDENTITY(%s) AS %s', $field, $event->getOwnerIdAlias()))
            ->andWhere($queryBuilder->expr()->in($field, ':entityIds'))
            ->setParameter('entityIds', $event->getEntityIds(), ArrayParameterType::INTEGER);

        $event->setRestricted(true);
    }
}
