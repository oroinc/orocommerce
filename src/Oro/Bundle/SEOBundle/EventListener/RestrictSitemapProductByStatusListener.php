<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;

class RestrictSitemapProductByStatusListener
{
    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $event
            ->getQueryBuilder()
            ->andWhere(UrlItemsProvider::ENTITY_ALIAS . '.status = :status')
            ->setParameter('status', Product::STATUS_ENABLED);
    }
}
