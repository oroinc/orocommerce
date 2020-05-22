<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LowInventoryProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityFallbackResolver;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var InventoryLevelRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $inventoryLevelRepository;

    /**
     * @var LowInventoryProvider
     */
    protected $lowInventoryProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
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

        $this->lowInventoryProvider = new LowInventoryProvider(
            $this->entityFallbackResolver,
            $this->doctrineHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset(
            $this->entityFallbackResolver,
            $this->doctrineHelper,
            $this->inventoryLevelRepository,
            $this->warehouseConfigConverter,
            $this->lowInventoryProvider
        );

        parent::tearDown();
    }

    /**
     * @param Product          $product
     * @param ProductUnit|null $productUnit
     * @param bool             $highlightLowInventory
     * @param bool             $lowInventoryThreshold
     * @param int              $inventoryLevelQuantity
     * @param bool             $expectedIsLowInventoryProduct
     * @param bool             $highlightLowInventoryCalled
     *
     * @dataProvider providerTestIsLowInventoryProduct
     */
    public function testIsLowInventoryProduct(
        Product $product,
        ProductUnit $productUnit = null,
        $highlightLowInventory,
        $lowInventoryThreshold,
        $inventoryLevelQuantity,
        $expectedIsLowInventoryProduct,
        $highlightLowInventoryCalled
    ) {
        if ($highlightLowInventoryCalled) {
            $this->entityFallbackResolver
                ->expects($this->at(0))
                ->method('getFallbackValue')
                ->with($product, LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION)
                ->willReturn($highlightLowInventory);

            $this->entityFallbackResolver
                ->expects($this->at(1))
                ->method('getFallbackValue')
                ->with($product, LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION)
                ->willReturn($lowInventoryThreshold);

            $this->doctrineHelper
                ->expects($this->once())
                ->method('getEntityRepositoryForClass')
                ->willReturn($this->inventoryLevelRepository);

            $inventoryLevel = $this->getInventoryLevel($inventoryLevelQuantity);

            $this->inventoryLevelRepository->expects($this->once())
                ->method('getLevelByProductAndProductUnit')
                ->willReturn($inventoryLevel);
        }

        $isLowInventoryProduct = $this->lowInventoryProvider->isLowInventoryProduct($product, $productUnit);

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
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => true,
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => 10,
                'inventoryLevelQuantity' => 5,
                'expectedIsLowInventoryProduct' => true,
                'highlightLowInventoryCalled' => true
            ],
            'is not low inventory: product and product unit' => [
                'product' => $this->getProductEntity(),
                'productUnit' => new ProductUnit(),
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => true,
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => 10,
                'inventoryLevelQuantity' => 15,
                'expectedIsLowInventoryProduct' => false,
                'highlightLowInventoryCalled' => true
            ],
            'is low inventory: product' => [
                'product' => $this->getProductEntity(),
                'productUnit' => null,
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => true,
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => 10,
                'inventoryLevelQuantity' => 5,
                'expectedIsLowInventoryProduct' => true,
                'highlightLowInventoryCalled' => true
            ],
            'is not low inventory: product ' => [
                'product' => $this->getProductEntity(),
                'productUnit' => null,
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => true,
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => 10,
                'inventoryLevelQuantity' => 15,
                'expectedIsLowInventoryProduct' => false,
                'highlightLowInventoryCalled' => true
            ],
            'is low inventory: product without primary unit' => [
                'product' => $this->getProductEntity(55, false),
                'productUnit' => null,
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => true,
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => 10,
                'inventoryLevelQuantity' => 15,
                'expectedIsLowInventoryProduct' => false,
                'highlightLowInventoryCalled' => false
            ]
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
                $this->equalTo(LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION),
                $this->equalTo(LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION)
            ))
            ->will($this->returnCallback(
                function (Product $product, $option) use ($highlightLowInventory, $lowInventoryThreshold) {
                    if ($option === LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION) {
                        return $highlightLowInventory[$product->getId()];
                    }
                    if ($option === LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION) {
                        return $lowInventoryThreshold[$product->getId()];
                    }
                }
            ));

        $this->doctrineHelper->expects($this->once())->method('getEntityRepositoryForClass')
            ->willReturn($this->inventoryLevelRepository);

        $this->inventoryLevelRepository->expects($this->once())
            ->method('getQuantityForProductCollection')
            ->willReturn($quantityForProductCollection);

        $isLowInventoryProduct = $this->lowInventoryProvider->isLowInventoryCollection($products);

        $this->assertEquals($expectedResult, $isLowInventoryProduct);
    }

    /**
     * @param string $productUnitCode
     *
     * @return array
     */
    protected function getTestProducts($productUnitCode)
    {
        return [
            [
                'product' => $this->getProductEntity(1),
                'product_unit' => $this->getProductUnitEntity($productUnitCode),
            ],
            [
                'product' => $this->getProductEntity(2),
                'product_unit' => $this->getProductUnitEntity($productUnitCode),
            ],
        ];
    }

    /**
     * @param float $itemQuantity
     * @param float $setQuantity
     *
     * @return array
     */
    protected function getQuantityForProductCollection($itemQuantity, $setQuantity)
    {
        return [
            [
                'product_id' => 1,
                'code' => 'item',
                'quantity' => $itemQuantity,
            ],
            [
                'product_id' => 1,
                'code' => 'set',
                'quantity' => $setQuantity,
            ],
            [
                'product_id' => 2,
                'code' => 'item',
                'quantity' => $itemQuantity,
            ],
            [
                'product_id' => 2,
                'code' => 'set',
                'quantity' => $setQuantity,
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerTestIsLowInventoryCollection()
    {
        return [
            '1. show low inventory for all products with enabled highlightLowInventory 
            (quantity < lowInventoryThreshold)' => [
                'products' => $this->getTestProducts('set'),
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => [
                    1 => true,
                    2 => true,
                ],
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(10, 5),
                'expectedResult' => [
                    1 => true,
                    2 => true,
                ],
            ],
            '2. hide low inventory for all products with enabled highlightLowInventory
            quantity > lowInventoryThreshold' => [
                'products' => $this->getTestProducts('set'),
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => [
                    1 => true,
                    2 => true,
                ],
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(15, 30),
                'expectedResult' => [
                    1 => false,
                    2 => false,
                ],
            ],
            '3. hide low inventory for all products with disabled highlightLowInventory' => [
                'products' => $this->getTestProducts('set'),
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => [
                    1 => false,
                    2 => false,
                ],
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(15, 30),
                'expectedResult' => [
                    1 => false,
                    2 => false,
                ],
            ],
            '4. show low inventory for product with enabled highlightLowInventory
            and hide product with disabled highlightLowInventory (quantity < lowInventoryThreshold)' => [
                'products' => $this->getTestProducts('item'),
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => [
                    1 => true,
                    2 => false,
                ],
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(5, 30),
                'expectedResult' => [
                    1 => true,
                    2 => false,
                ],
            ],
            '5. hide low inventory for product with enabled highlightLowInventory
            and hide product with disabled highlightLowInventory (quantity > lowInventoryThreshold)' => [
                'products' => $this->getTestProducts('item'),
                LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION => [
                    1 => true,
                    2 => false,
                ],
                LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION => [
                    1 => 10,
                    2 => 10,
                ],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(15, 30),
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
     * @param int|null $id
     * @param bool $withPrimaryUnitPrecision
     *
     * @return ProductStub
     */
    protected function getProductEntity($id = null, $withPrimaryUnitPrecision = true)
    {
        $product = new ProductStub();
        $product->setId($id);

        $unitPrecision = new ProductUnitPrecision();
        $productUnit = $this->getProductUnitEntity('item');
        $unitPrecision->setUnit($productUnit);

        if ($withPrimaryUnitPrecision) {
            $product->setPrimaryUnitPrecision($unitPrecision);
        }

        return $product;
    }

    /**
     * @param string $code
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
