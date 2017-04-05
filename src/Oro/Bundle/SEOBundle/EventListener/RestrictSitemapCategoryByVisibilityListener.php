<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\VisibilityBundle\Model\CategoryVisibilityQueryBuilderModifier;

class RestrictSitemapCategoryByVisibilityListener
{
    /**
     * @var CategoryVisibilityQueryBuilderModifier
     */
    private $categoryVisibilityQueryBuilderModifier;

    /**
     * @param CategoryVisibilityQueryBuilderModifier $categoryVisibilityQueryBuilderModifier
     */
    public function __construct(
        CategoryVisibilityQueryBuilderModifier $categoryVisibilityQueryBuilderModifier
    ) {
        $this->categoryVisibilityQueryBuilderModifier = $categoryVisibilityQueryBuilderModifier;
    }

    /**
     * @param RestrictSitemapEntitiesEvent $event
     */
    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        $this->categoryVisibilityQueryBuilderModifier->restrictForAnonymous($event->getQueryBuilder());
    }
}
