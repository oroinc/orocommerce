<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Loads disabled products kits.
 */
class LoadDisabledProductKitData extends LoadProductKitData
{
    public const PRODUCT_KIT_4 = 'product-kit-4';

    protected function getProductsData(): array
    {
        return [
            [
                'sku' => self::PRODUCT_KIT_4,
                'name' => 'Disabled Product Kit',
                'unit' => 'milliliter',
                'status' => Product::STATUS_DISABLED,
                'kitItems' => [
                    [
                        'label' => 'PKSKU4 - With Min and Max Quantity',
                        'unit' => 'liter',
                        'optional' => false,
                        'sortOrder' => 1,
                        'minimumQuantity' => 1,
                        'maximumQuantity' => 2,
                        'products' => ['product-1', 'product-2'],
                    ],
                    [
                        'label' => 'PKSKU4 - With Min Quantity',
                        'unit' => 'milliliter',
                        'optional' => false,
                        'sortOrder' => 2,
                        'minimumQuantity' => 2,
                        'maximumQuantity' => null,
                        'products' => ['product-3'],
                    ],
                    [
                        'label' => 'PKSKU4 - With Max Quantity',
                        'unit' => 'milliliter',
                        'optional' => false,
                        'sortOrder' => 2,
                        'minimumQuantity' => null,
                        'maximumQuantity' => 4,
                        'products' => ['product-4', 'product-5'],
                    ],
                ],
                'inventoryStatusId' => Product::INVENTORY_STATUS_IN_STOCK,
            ],
        ];
    }
}
