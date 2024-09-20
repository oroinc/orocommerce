<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;

/**
 * Provides label and code for inventory status by given Product, ProductView or search item
 */
class InventoryStatusProvider
{
    public function __construct(
        private EnumOptionsProvider $enumOptionsProvider,
        private ManagerRegistry $doctrine
    ) {
    }

    public function getLabel(Product|ProductView|array $product): ?string
    {
        $inventoryStatuses = array_flip(
            $this->enumOptionsProvider->getEnumChoicesByCode(Product::INVENTORY_STATUS_ENUM_CODE)
        );
        $inventoryStatus = $this->getEnumValue($product)?->getId();

        return $inventoryStatus ? ($inventoryStatuses[$inventoryStatus] ?? $inventoryStatus) : null;
    }

    public function getCode(Product|ProductView|array $product): ?string
    {
        return $this->getEnumValue($product)?->getId();
    }

    private function getEnumValue(Product|ProductView|array $product): ?EnumOptionInterface
    {
        if ($product instanceof Product && ($value = $product->getInventoryStatus())) {
            return $value;
        }

        $productId = null;

        if ($product instanceof ProductView && $product->has('id')) {
            $productId = $product->get('id');
        } elseif (is_array($product) && isset($product['id'])) { // Search result item
            $productId = $product['id'];
        }

        if (!$productId || !($data = $this->doctrine->getRepository(Product::class)->find($productId))) {
            return null;
        }

        return $this->getEnumValue($data);
    }
}
