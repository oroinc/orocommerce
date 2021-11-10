<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;

/**
 * Adds information required to highlight upcoming products to storefront product lists.
 */
class ProductListUpcomingLabelListener
{
    public function onBuildQuery(BuildQueryProductListEvent $event): void
    {
        $event->getQuery()
            ->addSelect('integer.is_upcoming')
            ->addSelect('datetime.availability_date');
    }

    public function onBuildResult(BuildResultProductListEvent $event): void
    {
        foreach ($event->getProductData() as $productId => $data) {
            $availabilityDate = $data['availability_date'];
            if ('' === $availabilityDate) {
                $availabilityDate = null;
            }
            $productView = $event->getProductView($productId);
            $productView->set('is_upcoming', (bool)$data['is_upcoming']);
            $productView->set('availability_date', $availabilityDate);
        }
    }
}
