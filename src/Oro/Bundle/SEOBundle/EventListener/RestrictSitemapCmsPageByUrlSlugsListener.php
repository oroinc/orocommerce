<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;

/**
 * Restricts sitemap building for cms pages without URL slugs
 */
class RestrictSitemapCmsPageByUrlSlugsListener
{
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $event
            ->getQueryBuilder()
            ->innerJoin(sprintf('%s.slugs', UrlItemsProvider::ENTITY_ALIAS), 'slugs');
    }
}
