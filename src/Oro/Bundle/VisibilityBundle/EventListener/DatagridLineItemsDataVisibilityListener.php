<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;

/**
 * Adds the resolved visibility to the line items data.
 */
class DatagridLineItemsDataVisibilityListener
{
    public const IS_VISIBLE = 'isVisible';

    private ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider;

    public function __construct(ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider)
    {
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItemId => $lineItem) {
            $lineItemProduct = $lineItem->getProduct();
            $isVisible = $lineItemProduct?->isEnabled()
                ? $this->resolvedProductVisibilityProvider->isVisible($lineItemProduct->getId())
                : false;

            $event->addDataForLineItem(
                $lineItemId,
                [self::IS_VISIBLE => $isVisible]
            );
        }
    }
}
