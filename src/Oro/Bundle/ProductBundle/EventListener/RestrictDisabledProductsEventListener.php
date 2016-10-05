<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

/**
 * Hide all not enabled products
 */
class RestrictDisabledProductsEventListener
{
    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @param ProductVisibilityQueryBuilderModifier $modifier
     */
    public function __construct(ProductVisibilityQueryBuilderModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    /**
     * @param ProductDBQueryRestrictionEvent $event
     */
    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        $this->modifier->modifyByStatus($event->getQueryBuilder(), [Product::STATUS_ENABLED]);
    }
}
