<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
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
    private array $allowedInventoryStatusOptionId = [Product::INVENTORY_STATUS_IN_STOCK];
    private string $availableInventoryStatusOptionId = Product::INVENTORY_STATUS_IN_STOCK;
    private string $unavailableInventoryStatusOptionId = Product::INVENTORY_STATUS_OUT_OF_STOCK;

    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function setAllowedInventoryStatusOptionId(array $allowedInventoryStatusOptionId): void
    {
        $this->allowedInventoryStatusOptionId = $allowedInventoryStatusOptionId;
    }

    public function setAvailableInventoryStatusOptionId(string $availableInventoryStatusOptionId): void
    {
        $this->availableInventoryStatusOptionId = $availableInventoryStatusOptionId;
    }

    public function setUnavailableInventoryStatusOptionId(string $unavailableInventoryStatusOptionId): void
    {
        $this->unavailableInventoryStatusOptionId = $unavailableInventoryStatusOptionId;
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
        $allowedOptionIds = ExtendHelper::mapToEnumOptionIds(
            Product::INVENTORY_STATUS_ENUM_CODE,
            $this->allowedInventoryStatusOptionId
        );
        foreach ($products as $product) {
            $productData = $data[$product->getId()] ?? [];

            foreach ($productData as $datum) {
                // If within product kit item at least one is in stock then product kit is in stock
                if (!array_intersect($allowedOptionIds, $datum['status'])) {
                    $product->setInventoryStatus($this->getInventoryStatus($this->unavailableInventoryStatusOptionId));
                    continue 2;
                }
            }

            $product->setInventoryStatus($this->getInventoryStatus($this->availableInventoryStatusOptionId));
        }
    }

    private function getInventoryStatus(string $inventoryStatusId): EnumOptionInterface
    {
        return $this->registry->getRepository(EnumOption::class)->findOneBy([
            'id' => ExtendHelper::buildEnumOptionId(
                Product::INVENTORY_STATUS_ENUM_CODE,
                $inventoryStatusId
            )
        ]);
    }
}
