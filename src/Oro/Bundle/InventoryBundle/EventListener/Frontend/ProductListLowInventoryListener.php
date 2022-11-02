<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\BuildQueryProductListEvent;
use Oro\Bundle\ProductBundle\Event\BuildResultProductListEvent;

/**
 * Adds information required to highlight low inventory products to storefront product lists.
 */
class ProductListLowInventoryListener
{
    private LowInventoryProvider $lowInventoryProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        LowInventoryProvider $lowInventoryProvider,
        ManagerRegistry $doctrine
    ) {
        $this->lowInventoryProvider = $lowInventoryProvider;
        $this->doctrine = $doctrine;
    }

    public function onBuildQuery(BuildQueryProductListEvent $event): void
    {
        $event->getQuery()
            ->addSelect('decimal.low_inventory_threshold');
    }

    public function onBuildResult(BuildResultProductListEvent $event): void
    {
        $lowInventoryData = [];
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);
        foreach ($event->getProductData() as $productId => $data) {
            $lowInventoryThreshold = $data['low_inventory_threshold'];
            $lowInventoryData[] = [
                'product' => $em->getReference(Product::class, $productId),
                'product_unit' => $em->getReference(ProductUnit::class, $data['unit']),
                'low_inventory_threshold' => $lowInventoryThreshold ?: -1,
                'highlight_low_inventory' => (bool)$lowInventoryThreshold
            ];
        }

        $lowInventoryResponse = $this->lowInventoryProvider->isLowInventoryCollection($lowInventoryData);

        foreach ($event->getProductData() as $productId => $data) {
            $productView = $event->getProductView($productId);
            $productView->set('low_inventory', $lowInventoryResponse[$productId] ?? false);
        }
    }
}
