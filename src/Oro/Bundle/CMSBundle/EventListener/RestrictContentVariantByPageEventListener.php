<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntitiesEvent;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntityEvent;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Restrict content variant query builder by a given page or by a set of pages.
 */
class RestrictContentVariantByPageEventListener
{
    private const string ASSOCIATION = 'cms_page';

    public function applyRestriction(RestrictContentVariantByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Page) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq(
                    QueryBuilderUtil::getField($event->getVariantAlias(), self::ASSOCIATION),
                    ':page'
                ))
                ->setParameter('page', $entity);
        }
    }

    public function applyRestrictionForEntities(RestrictContentVariantByEntitiesEvent $event): void
    {
        if (!is_a($event->getEntityClass(), Page::class, true)) {
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
