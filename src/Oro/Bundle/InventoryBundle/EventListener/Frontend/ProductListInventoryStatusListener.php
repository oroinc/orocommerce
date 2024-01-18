<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;

/**
 * Adds information required to display inventory status to storefront product lists.
 */
class ProductListInventoryStatusListener
{
    protected EnumValueProvider $enumValueProvider;

    public function __construct(EnumValueProvider $enumValueProvider)
    {
        $this->enumValueProvider = $enumValueProvider;
    }

    public function onBuildQuery(BuildQueryProductListEvent $event): void
    {
        $event->getQuery()
            ->addSelect('text.inv_status as inventory_status');
    }

    public function onBuildResult(BuildResultProductListEvent $event): void
    {
        $inventoryStatuses = array_flip(
            $this->enumValueProvider->getEnumChoicesByCode('prod_inventory_status')
        );

        foreach ($event->getProductData() as $productId => $data) {
            $productView = $event->getProductView($productId);
            $inventoryStatus = $data['inventory_status'] ?? null;
            $productView->set('inventory_status', $inventoryStatus);
            $productView->set('inventory_status_label', $inventoryStatuses[$inventoryStatus] ?? $inventoryStatus);
        }
    }
}
