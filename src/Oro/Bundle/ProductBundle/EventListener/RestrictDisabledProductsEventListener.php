<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;

/**
 * Hide all not enabled products
 */
class RestrictDisabledProductsEventListener
{
    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    private $searchQueryModifier;

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    private $queryBuilderModifier;

    /**
     * @param ProductVisibilitySearchQueryModifier  $searchQueryModifier
     * @param ProductVisibilityQueryBuilderModifier $queryBuilderModifier
     */
    public function __construct(
        ProductVisibilitySearchQueryModifier $searchQueryModifier,
        ProductVisibilityQueryBuilderModifier $queryBuilderModifier
    ) {
        $this->searchQueryModifier  = $searchQueryModifier;
        $this->queryBuilderModifier = $queryBuilderModifier;
    }

    /**
     * @param ProductSearchQueryRestrictionEvent $event
     */
    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        $this->searchQueryModifier->modifyByStatus($event->getQuery(), [Product::STATUS_ENABLED]);
    }

    /**
     * @param ProductDBQueryRestrictionEvent $event
     */
    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        $this->queryBuilderModifier->modifyByStatus($event->getQueryBuilder(), [Product::STATUS_ENABLED]);
    }
}
