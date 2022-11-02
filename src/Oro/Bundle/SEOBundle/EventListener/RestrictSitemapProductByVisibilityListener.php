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
