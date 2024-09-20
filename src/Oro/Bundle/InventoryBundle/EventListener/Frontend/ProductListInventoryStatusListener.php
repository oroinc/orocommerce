<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;

/**
 * Adds information required to display inventory status to storefront product lists.
 */
class ProductListInventoryStatusListener
{
    public function __construct(protected EnumOptionsProvider $enumOptionsProvider)
    {
    }

    public function onBuildQuery(BuildQueryProductListEvent $event): void
    {
        $event->getQuery()
            ->addSelect('text.inv_status as inventory_status');
    }

    public function onBuildResult(BuildResultProductListEvent $event): void
    {
        $enumInventoryChoices = $this->enumOptionsProvider
            ->getEnumInternalChoices(Product::INVENTORY_STATUS_ENUM_CODE);

        foreach ($event->getProductData() as $productId => $data) {
            $productView = $event->getProductView($productId);
            $inventoryStatus = $data['inventory_status'] ?? null;
            if (is_string($inventoryStatus)) {
                $inventoryStatus = ExtendHelper::getEnumInternalId($inventoryStatus);
            }
            $productView->set('inventory_status', $inventoryStatus);
            $productView->set('inventory_status_label', $enumInventoryChoices[$inventoryStatus] ?? $inventoryStatus);
        }
    }
}
