<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

/** This listener modify product variants queries with visibility conditions */
class RestrictProductVariantEventVisibilityListener
{
    /** @var ProductVisibilityQueryBuilderModifier */
    protected $modifier;

    /** @param ProductVisibilityQueryBuilderModifier $modifier */
    public function __construct(ProductVisibilityQueryBuilderModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    /** @param RestrictProductVariantEvent $event */
    public function onRestrictProductVariantEvent(RestrictProductVariantEvent $event)
    {
        $this->modifier->modify($event->getQueryBuilder());
    }
}
