<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

/**
 * Applies product visibility restrictions to database queries on the storefront.
 *
 * This listener handles {@see ProductDBQueryRestrictionEvent} events and modifies query builders to filter products
 * based on visibility settings. It ensures that only products visible to the current customer or customer group
 * are included in query results on the frontend.
 */
class ProductDBQueryRestrictionEventListener
{
    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @var QueryBuilderModifierInterface|ProductVisibilityQueryBuilderModifier
     */
    private $modifier;

    public function __construct(FrontendHelper $frontendHelper, QueryBuilderModifierInterface $modifier)
    {
        $this->frontendHelper = $frontendHelper;
        $this->modifier       = $modifier;
    }

    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $this->modifier->modify($event->getQueryBuilder());
        }
    }
}
