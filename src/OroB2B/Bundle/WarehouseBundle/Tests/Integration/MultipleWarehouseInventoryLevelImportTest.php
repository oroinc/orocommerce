<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Integration;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class MultipleWarehouseInventoryLevelImportTest extends BaseWarehouseInventoryLevelImportTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels'
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
                    'Quantity' => 'quantity',
                    'Warehouse' => 'warehouse:name',
                ],
                [
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'In Stock',
                            'Warehouse' => 'First Warehouse',
                            'Quantity' => 100
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'Out of Stock',
                            'Warehouse' => 'First Warehouse',
                            'Quantity' => 200
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Product' => '"Medical Tag, Intentional Typo Here"',
                            'Inventory Status' => '',
                            'Warehouse' => 'First Warehouse',
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
                    'Warehouse' => 'warehouse:name',
                    'Unit' => 'productUnitPrecision:unit:code'
                ],
                [
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => '',
                            'Quantity' => 2000,
                            'Warehouse' => 'Second Warehouse',
                            'Unit' => 'liters'
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
                            'Inventory Status' => '',
                            'Quantity' => 700,
                            'Warehouse' => 'Second Warehouse',
                            'Unit' => 'milliliter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => '',
                            'Quantity' => 100,
                            'Warehouse' => 'First Warehouse',
                            'Unit' => 'liter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => '',
                            'Quantity' => 1000,
                            'Warehouse' => 'Second Warehouse',
                            'Unit' => 'liter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => '',
                            'Quantity' => 550,
                            'Warehouse' => 'Second Warehouse',
                            'Unit' => 'milliliter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'In Stock',
                            'Quantity' => 55,
                            'Warehouse' => 'First Warehouse',
                            'Unit' => 'milliliter'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Product' => '"Medical Tag, Stainless Steel"',
                            'Inventory Status' => 'Out of Stock',
                            'Quantity' => 200,
                            'Warehouse' => 'First Warehouse',
                            'Unit' => 'liters'
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
                            'Inventory Status' => '',
                            'Quantity' => 77,
                            'Warehouse' => 'First Warehouse',
                            'Unit' => 'milliliters'
                        ],
                        'options' => [
                            'singularize' => ['Unit']
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Product' => '"Medical Tag, Intentional Typo Here"',
                            'Inventory Status' => '',
                            'Quantity' => 300,
                            'Warehouse' => 'First Warehouse',
                            'Unit' => ''
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Product' => '"Medical Tag, Intentional Typo Here"',
                            'Inventory Status' => '',
                            'Quantity' => 300,
                            'Warehouse' => 'Second Warehouse',
                            'Unit' => ''
                        ]
                    ]
                ]
            ],
            [
                [
                    'SKU' => 'sku',
                    'Inventory Status' => 'inventoryStatus:name',
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
                            'Inventory Status' => 'Out of Stock'
                        ]
                    ],
                    [
                        'class' => Product::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Product' => '"Medical Tag, Intentional Typo Here"',
                            'Inventory Status' => 'Discontinued'
                        ]
                    ]
                ]
            ],
            [
                [
                    'SKU' => 'product:sku',
                    'Quantity' => 'quantity',
                    'Warehouse' => 'warehouse:name',
                ],
                [
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.1',
                            'Quantity' => 100,
                            'Warehouse' => 'First Warehouse'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Quantity' => 200,
                            'Warehouse' => 'First Warehouse'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.2',
                            'Quantity' => 2000,
                            'Warehouse' => 'Second Warehouse'
                        ]
                    ],
                    [
                        'class' => WarehouseInventoryLevel::class,
                        'data' => [
                            'SKU' => 'product.3',
                            'Quantity' => 300,
                            'Warehouse' => 'First Warehouse'
                        ]
                    ]
                ]
            ],
        ];
    }
}
