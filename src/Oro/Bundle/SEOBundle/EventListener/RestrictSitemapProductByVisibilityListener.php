<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictSitemapProductByVisibilityListener
{
    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    private $productVisibilityQueryBuilderModifier;

    /**
     * @param ProductVisibilityQueryBuilderModifier $productVisibilityQueryBuilderModifier
     */
    public function __construct(ProductVisibilityQueryBuilderModifier $productVisibilityQueryBuilderModifier)
    {
        $this->productVisibilityQueryBuilderModifier = $productVisibilityQueryBuilderModifier;
    }

    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $this->productVisibilityQueryBuilderModifier->restrictForAnonymous(
            $event->getQueryBuilder(),
            $event->getWebsite()
        );
    }
}
