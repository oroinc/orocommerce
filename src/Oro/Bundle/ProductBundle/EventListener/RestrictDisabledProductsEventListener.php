<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;

/**
 * Hide all not enabled products
 */
class RestrictDisabledProductsEventListener
{
    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    protected $modifier;

    /**
     * @param ProductVisibilitySearchQueryModifier $modifier
     */
    public function __construct(ProductVisibilitySearchQueryModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    /**
     * @param ProductSearchQueryRestrictionEvent $event
     */
    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        $this->modifier->modifyByStatus($event->getQuery(), [Product::STATUS_ENABLED]);
    }
}
