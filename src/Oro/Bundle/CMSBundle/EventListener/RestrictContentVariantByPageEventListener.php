<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\WebCatalogBundle\Event\RestrictContentVariantByEntityEvent;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Restrict content variant query builder by a given page.
 */
class RestrictContentVariantByPageEventListener
{
    public function applyRestriction(RestrictContentVariantByEntityEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Page) {
            $queryBuilder = $event->getQueryBuilder();
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq(
                    QueryBuilderUtil::getField($event->getVariantAlias(), 'cms_page'),
                    ':page'
                ))
                ->setParameter('page', $entity);
        }
    }
}
