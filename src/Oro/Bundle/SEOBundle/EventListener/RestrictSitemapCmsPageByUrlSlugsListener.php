<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;

/**
 * Restricts sitemap building for cms pages without URL slugs
 */
class RestrictSitemapCmsPageByUrlSlugsListener
{
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event): void
    {
        $queryBuilder = $event->getQueryBuilder();

        if ($this->isAliasJoined($queryBuilder, 'slugs')) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNotNull('slugs.id'));
        } else {
            $queryBuilder->innerJoin(sprintf('%s.slugs', UrlItemsProvider::ENTITY_ALIAS), 'slugs');
        }
    }

    private function isAliasJoined(QueryBuilder $queryBuilder, string $alias): bool
    {
        foreach ($queryBuilder->getDQLPart('join') as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $alias) {
                    return true;
                }
            }
        }

        return false;
    }
}
