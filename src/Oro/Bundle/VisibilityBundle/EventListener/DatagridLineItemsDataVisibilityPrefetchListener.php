<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;

/**
 * Prefetches the resolved visibility for the line items' products.
 */
class DatagridLineItemsDataVisibilityPrefetchListener
{
    private ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider;

    public function __construct(ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider)
    {
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $productIds = [];
        foreach ($event->getLineItems() as $lineItemId => $lineItem) {
            $lineItemProduct = $lineItem->getProduct();
            if (!$lineItemProduct?->isEnabled()) {
                continue;
            }

            $productId = $lineItemProduct->getId();
            $productIds[$productId] = $productId;

            if ($lineItem instanceof ProductKitItemLineItemsAwareInterface) {
                $lineItemType = $event->getDataForLineItem($lineItemId)['type'] ?? '';
                if ($lineItemType === Product::TYPE_KIT) {
                    foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                        $kitItemLineItemProduct = $kitItemLineItem->getProduct();
                        if (!$kitItemLineItemProduct?->isEnabled()) {
                            continue;
                        }

                        $productId = $kitItemLineItemProduct->getId();
                        $productIds[$productId] = $productId;
                    }
                }
            }
        }

        $this->resolvedProductVisibilityProvider->prefetch(array_values($productIds));
    }
}
