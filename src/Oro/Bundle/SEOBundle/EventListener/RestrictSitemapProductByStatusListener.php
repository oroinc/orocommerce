<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;

/**
 * Restricts sitemap to include only enabled products.
 *
 * This listener filters the product query builder to exclude disabled products from the sitemap.
 * It ensures that only products with STATUS_ENABLED are included in the generated sitemap, preventing
 * disabled or inactive products from being indexed by search engines.
 */
class RestrictSitemapProductByStatusListener
{
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $event
            ->getQueryBuilder()
            ->andWhere(UrlItemsProvider::ENTITY_ALIAS . '.status = :status')
            ->setParameter('status', Product::STATUS_ENABLED);
    }
}
