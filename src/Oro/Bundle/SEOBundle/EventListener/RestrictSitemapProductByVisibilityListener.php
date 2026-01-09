<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

/**
 * Restricts sitemap to include only products visible to anonymous users.
 *
 * This listener applies product visibility restrictions to the sitemap query builder, ensuring that only products
 * visible to anonymous (guest) users are included in the generated sitemap. This respects the product visibility
 * configuration and prevents products with restricted visibility from being indexed by search engines.
 */
class RestrictSitemapProductByVisibilityListener
{
    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    private $productVisibilityQueryBuilderModifier;

    public function __construct(ProductVisibilityQueryBuilderModifier $productVisibilityQueryBuilderModifier)
    {
        $this->productVisibilityQueryBuilderModifier = $productVisibilityQueryBuilderModifier;
    }

    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $this->productVisibilityQueryBuilderModifier->restrictForAnonymous(
            $event->getQueryBuilder(),
            $event->getWebsite()
        );
    }
}
