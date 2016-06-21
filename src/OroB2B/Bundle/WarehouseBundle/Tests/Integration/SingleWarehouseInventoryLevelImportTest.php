<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Integration;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class SingleWarehouseInventoryLevelImportTest extends BaseWarehouseInventoryLevelImportTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\WarehouseBundle\Tests\Integration\DataFixtures\LoadWarehousesAndInventoryLevels'
            ]
        );
    }

    /**
     * return array
     */
    public function processDataProvider()
    {
        return [
            [
                [
                    'SKU' => 'sku',
                    'Inventory Status' => 'inventoryStatus:name'
                ],
                [
                    [
                        'class' => Product::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'In Stock'
                        ]
                    ],
                    [
                        'class' => Product::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'In Stock'
                        ]
                    ],
                    [
                        'class' => Product::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Product' => '"Medical Tag, Intentional Typo Here"',
                            'Inventory Status' => ''
                        ]
                    ]
                ]
            ],
            [
                [
                    'SKU' => 'product:sku',
                    'Inventory Status' => 'product:inventoryStatus:name',
                    'Quantity' => 'quantity'
                ],
                [
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'In Stock',
                            'Quantity' => 100
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'Out of Stock',
                            'Quantity' => 200
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Product' => '"Medical Tag, Intentional Typo Here"',
                            'Inventory Status' => '',
                            'Quantity' => 300
                        ]
                    ]
                ]
            ],
            [
                [
                    'SKU' => 'product:sku',
                    'Inventory Status' => 'product:inventoryStatus:name',
                    'Quantity' => 'quantity',
                    'Unit' => 'productUnitPrecision:unit:code'
                ],
                [
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'In Stock',
                            'Quantity' => 100,
                            'Unit' => 'liters'
                        ],
                        'options' => [
                            'singularize' => ['Unit']
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'In Stock',
                            'Quantity' => 55,
                            'Unit' => 'milliliters'
                        ],
                        'options' => [
                            'singularize' => ['Unit']
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'Out of Stock',
                            'Quantity' => 200,
                            'Unit' => 'liter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => '',
                            'Quantity' => 77,
                            'Unit' => 'milliliter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Product' => '"Medical Tag, Intentional Typo Here"',
                            'Inventory Status' => '',
                            'Quantity' => 300,
                            'Unit' => ''
                        ]
                    ]
                ]
            ],
            [
                [
                    'SKU' => 'product:sku',
                    'Quantity' => 'quantity',
                    'Unit' => 'productUnitPrecision:unit:code'
                ],
                [
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Quantity' => 100,
                            'Unit' => 'liter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Quantity' => 55,
                            'Unit' => 'milliliter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Quantity' => 200,
                            'Unit' => 'liter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Quantity' => 77,
                            'Unit' => 'milliliter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Quantity' => 300,
                            'Unit' => ''
                        ]
                    ]
                ]
            ],
            [
                [
                    'SKU' => 'product:sku',
                    'Quantity' => 'quantity'
                ],
                [
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Quantity' => 100
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Quantity' => 200
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Quantity' => 300
                        ]
                    ]
                ]
            ],
        ];
    }
}
