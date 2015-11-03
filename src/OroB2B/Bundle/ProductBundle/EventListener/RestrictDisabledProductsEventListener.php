<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

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
