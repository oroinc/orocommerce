<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LowInventoryQuantityManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFallbackResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFallbackResolver;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var InventoryLevelRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inventoryLevelRepository;

    /**
     * @var LowInventoryQuantityManager
     */
    protected $lowInventoryQuantityManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->entityFallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryLevelRepository = $this->getMockBuilder(InventoryLevelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lowInventoryQuantityManager = new LowInventoryQuantityManager(
            $this->entityFallbackResolver,
            $this->doctrineHelper
        );
    }

    /**
     * @param Product          $product
     * @param ProductUnit|null $productUnit
     * @param bool             $highlightLowInventory
     * @param bool             $lowInventoryThreshold
     * @param int              $inventoryLevelQuantity
     * @param bool             $expectedIsLowInventoryProduct
     *
     * @dataProvider providerTestIsLowInventoryProduct
     */
    public function testIsLowInventoryProduct(
        Product $product,
        ProductUnit $productUnit = null,
        $highlightLowInventory,
        $lowInventoryThreshold,
        $inventoryLevelQuantity,
        $expectedIsLowInventoryProduct
    ) {
        $this->entityFallbackResolver
            ->expects($this->at(0))
            ->method('getFallbackValue')
            ->with($product, 'highlightLowInventory')->willReturn($highlightLowInventory);

        if ($highlightLowInventory) {
            $this->entityFallbackResolver
                ->expects($this->at(1))
                ->method('getFallbackValue')
                ->with($product, 'lowInventoryThreshold')->willReturn($lowInventoryThreshold);


            $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')
                ->willReturn($this->inventoryLevelRepository);

            $inventoryLevel = $this->getInventoryLevel($inventoryLevelQuantity);

            $this->inventoryLevelRepository->expects($this->once())
                ->method('getLevelByProductAndProductUnit')
                ->willReturn($inventoryLevel);
        }

        $isLowInventoryProduct = $this->lowInventoryQuantityManager->isLowInventoryProduct($product, $productUnit);

        $this->assertEquals($expectedIsLowInventoryProduct, $isLowInventoryProduct);
    }

    /**
     * @return array
     */
    public function providerTestIsLowInventoryProduct()
    {
        return [
            'is low inventory: product and product unit' => [
                'product' => $this->getProductEntity(),
                'productUnit' => new ProductUnit(),
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 5,
                'expectedIsLowInventoryProduct' => true,
            ],
            'is not low inventory: product and product unit' => [
                'product' => $this->getProductEntity(),
                'productUnit' => new ProductUnit(),
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 15,
                'expectedIsLowInventoryProduct' => false,
            ],
            'is low inventory: product' => [
                'product' => $this->getProductEntity(),
                'productUnit' => null,
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 5,
                'expectedIsLowInventoryProduct' => true,
            ],
            'is not low inventory: product ' => [
                'product' => $this->getProductEntity(),
                'productUnit' => null,
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 15,
                'expectedIsLowInventoryProduct' => false,
            ],
            'highlightLowInventory disabled' => [
                'product' => $this->getProductEntity(),
                'productUnit' => null,
                'highlightLowInventory' => false,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 15,
                'expectedIsLowInventoryProduct' => false,
            ],
        ];
    }

    /**
     * @dataProvider providerTestIsLowInventoryCollection
     */
    public function testIsLowInventoryCollection(
        $products,
        $highlightLowInventory,
        $lowInventoryThreshold,
        $quantityForProductCollection,
        $expectedResult
    ) {
        $this->entityFallbackResolver
            ->expects($this->any())
            ->method('getFallbackValue')
            ->with($this->logicalOr($products[0]['product'], $products[1]['product']), $this->logicalOr(
                $this->equalTo('highlightLowInventory'),
                $this->equalTo('lowInventoryThreshold')
            ))
            ->will($this->returnCallback(
                function (Product $product, $option) use ($highlightLowInventory, $lowInventoryThreshold) {
                    if ($option === 'highlightLowInventory') {
                        return $highlightLowInventory[$product->getId()];
                    }
                    if ($option === 'lowInventoryThreshold') {
                        return $lowInventoryThreshold[$product->getId()];
                    }
                }
            ));

        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')
            ->willReturn($this->inventoryLevelRepository);

        $this->inventoryLevelRepository->expects($this->once())
            ->method('getQuantityForProductCollection')
            ->willReturn($quantityForProductCollection);

        $isLowInventoryProduct = $this->lowInventoryQuantityManager->isLowInventoryCollection($products);

        $this->assertEquals($expectedResult, $isLowInventoryProduct);
    }

    public function providerTestIsLowInventoryCollection()
    {
        return [
            '1. show low inventory for all products with enabled highlightLowInventory 
            (quantity < lowInventoryThreshold)' => [
                'products' => [
                    [
                        'product' => $this->getProductEntity(1),
                        'product_unit' => $this->getProductUnitEntity('set'),
                    ],
                    [
                        'product' => $this->getProductEntity(2),
                        'product_unit' => $this->getProductUnitEntity('set'),
                    ],
                ],
                'highlightLowInventory' => [
                    1 => true,
                    2 => true,
                ],
                'lowInventoryThreshold' => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => [
                    [
                        'product_id' => 1,
                        'code' => 'item',
                        'quantity' => 10,
                    ],
                    [
                        'product_id' => 1,
                        'code' => 'set',
                        'quantity' => 5,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'item',
                        'quantity' => 10,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'set',
                        'quantity' => 5,
                    ],
                ],
                'expectedResult' => [
                    1 => true,
                    2 => true,
                ],
            ],
            '2. hide low inventory for all products with enabled highlightLowInventory
            quantity > lowInventoryThreshold' => [
                'products' => [
                    [
                        'product' => $this->getProductEntity(1),
                        'product_unit' => $this->getProductUnitEntity('set'),
                    ],
                    [
                        'product' => $this->getProductEntity(2),
                        'product_unit' => $this->getProductUnitEntity('set'),
                    ],
                ],
                'highlightLowInventory' => [
                    1 => true,
                    2 => true,
                ],
                'lowInventoryThreshold' => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => [
                    [
                        'product_id' => 1,
                        'code' => 'item',
                        'quantity' => 15,
                    ],
                    [
                        'product_id' => 1,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'item',
                        'quantity' => 15,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                ],
                'expectedResult' => [
                    1 => false,
                    2 => false,
                ],
            ],
            '3. hide low inventory for all products with disabled highlightLowInventory' => [
                'products' => [
                    [
                        'product' => $this->getProductEntity(1),
                        'product_unit' => $this->getProductUnitEntity('set'),
                    ],
                    [
                        'product' => $this->getProductEntity(2),
                        'product_unit' => $this->getProductUnitEntity('set'),
                    ],
                ],
                'highlightLowInventory' => [
                    1 => false,
                    2 => false,
                ],
                'lowInventoryThreshold' => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => [
                    [
                        'product_id' => 1,
                        'code' => 'item',
                        'quantity' => 15,
                    ],
                    [
                        'product_id' => 1,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'item',
                        'quantity' => 15,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                ],
                'expectedResult' => [
                    1 => false,
                    2 => false,
                ],
            ],
            '4. show low inventory for product with enabled highlightLowInventory
            and hide product with disabled highlightLowInventory (quantity < lowInventoryThreshold)' => [
                'products' => [
                    [
                        'product' => $this->getProductEntity(1),
                        'product_unit' => $this->getProductUnitEntity('item'),
                    ],
                    [
                        'product' => $this->getProductEntity(2),
                        'product_unit' => $this->getProductUnitEntity('item'),
                    ],
                ],
                'highlightLowInventory' => [
                    1 => true,
                    2 => false,
                ],
                'lowInventoryThreshold' => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => [
                    [
                        'product_id' => 1,
                        'code' => 'item',
                        'quantity' => 5,
                    ],
                    [
                        'product_id' => 1,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'item',
                        'quantity' => 5,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                ],
                'expectedResult' => [
                    1 => true,
                    2 => false,
                ],
            ],
            '5. hide low inventory for product with enabled highlightLowInventory
            and hide product with disabled highlightLowInventory (quantity > lowInventoryThreshold)' => [
                'products' => [
                    [
                        'product' => $this->getProductEntity(1),
                        'product_unit' => $this->getProductUnitEntity('item'),
                    ],
                    [
                        'product' => $this->getProductEntity(2),
                        'product_unit' => $this->getProductUnitEntity('item'),
                    ],
                ],
                'highlightLowInventory' => [
                    1 => true,
                    2 => false,
                ],
                'lowInventoryThreshold' => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => [
                    [
                        'product_id' => 1,
                        'code' => 'item',
                        'quantity' => 15,
                    ],
                    [
                        'product_id' => 1,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'item',
                        'quantity' => 5,
                    ],
                    [
                        'product_id' => 2,
                        'code' => 'set',
                        'quantity' => 30,
                    ],
                ],
                'expectedResult' => [
                    1 => false,
                    2 => false,
                ],
            ],
        ];
    }

    /**
     * @param int $quantity
     *
     * @return InventoryLevel
     */
    protected function getInventoryLevel($quantity)
    {
        $inventoryLevel = new InventoryLevel();
        $inventoryLevel->setQuantity($quantity);

        return $inventoryLevel;
    }

    /**
     * @return Product
     */
    protected function getProductEntity($id = null)
    {
        $product = new ProductStub();
        $product->setId($id);
        $primaryUnitPrecision = new ProductUnitPrecision();
        $productUnit = $this->getProductUnitEntity('item');
        $primaryUnitPrecision->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($primaryUnitPrecision);

        return $product;
    }

    /**
     * @param $code
     *
     * @return ProductUnit
     */
    protected function getProductUnitEntity($code)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }
}
