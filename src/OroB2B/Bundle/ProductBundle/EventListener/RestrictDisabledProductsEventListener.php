<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
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
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        $this->modifier->modifyByStatus($event->getQueryBuilder(), [Product::STATUS_ENABLED]);
    }
}
