<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Resolves and sets the current inventory status for product kit using next rules
 * - Out of stock product kit if all products from any of the required kit items become is not In Stock.
 * - In Stock product kit if all products from within every required kit items become In Stock.
 *
 * Note: ONLY required product kit items taken into account
 */
class ProductKitInventoryStatusResolver
{
    private ManagerRegistry $registry;

    private array $allowedInventoryStatuses = [Product::INVENTORY_STATUS_IN_STOCK];

    private string $availableInventoryStatus = Product::INVENTORY_STATUS_IN_STOCK;

    private string $unavailableInventoryStatus = Product::INVENTORY_STATUS_OUT_OF_STOCK;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function setAllowedInventoryStatuses(array $allowedInventoryStatuses): void
    {
        $this->allowedInventoryStatuses = $allowedInventoryStatuses;
    }

    public function setAvailableInventoryStatus(string $availableInventoryStatus): void
    {
        $this->availableInventoryStatus = $availableInventoryStatus;
    }

    public function setUnavailableInventoryStatus(string $unavailableInventoryStatus): void
    {
        $this->unavailableInventoryStatus = $unavailableInventoryStatus;
    }

    public function resolve(Product ...$products): void
    {
        $products = array_filter($products, static fn (Product $product) => $product->getId() && $product->isKit());
        if (empty($products)) {
            return;
        }

        $data = $this->registry->getRepository(ProductKitItem::class)->getRequiredProductKitItemInventoryStatuses(
            ...array_map(static fn (Product $product) => $product->getId(), $products)
        );

        foreach ($products as $product) {
            $productData = $data[$product->getId()] ?? [];

            foreach ($productData as $datum) {
                // If within product kit item at least one is in stock then product kit is in stock
                if (!array_intersect($this->allowedInventoryStatuses, $datum['status'])) {
                    $product->setInventoryStatus($this->getInventoryStatus($this->unavailableInventoryStatus));
                    continue 2;
                }
            }

            $product->setInventoryStatus($this->getInventoryStatus($this->availableInventoryStatus));
        }
    }

    private function getInventoryStatus(string $inventoryStatusId): AbstractEnumValue
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');

        return $this->registry->getRepository($inventoryStatusClassName)->findOneBy([
            'id' => $inventoryStatusId,
        ]);
    }
}
