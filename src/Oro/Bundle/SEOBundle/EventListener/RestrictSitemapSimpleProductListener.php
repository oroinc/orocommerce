<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;

/**
 * The listener that adds restriction condition to the SEO URL Items query
 * that filters out all products that are variation of configurable product
 */
class RestrictSitemapSimpleProductListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private QueryBuilderModifierInterface $dbQueryBuilderModifier;

    public function __construct(
        QueryBuilderModifierInterface $dbQueryBuilderModifier
    ) {
        $this->dbQueryBuilderModifier = $dbQueryBuilderModifier;
    }

    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event)
    {
        if ($this->isFeaturesEnabled()) {
            $this->dbQueryBuilderModifier->modify($event->getQueryBuilder());
        }
    }
}
