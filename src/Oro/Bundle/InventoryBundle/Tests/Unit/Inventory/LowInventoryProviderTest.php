<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\ReflectionUtil;

class LowInventoryProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityFallbackResolver;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var InventoryLevelRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $inventoryLevelRepository;

    /** @var LowInventoryProvider */
    protected $lowInventoryProvider;

    protected function setUp(): void
    {
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);

        $this->lowInventoryProvider = new LowInventoryProvider(
            $this->entityFallbackResolver,
            $this->doctrineHelper
        );
    }

    /**
     * @dataProvider providerTestIsLowInventoryProduct
     */
    public function testIsLowInventoryProduct(
        Product $product,
        ?ProductUnit $productUnit,
        bool $highlightLowInventory,
        int $lowInventoryThreshold,
        int $inventoryLevelQuantity,
        bool $expectedIsLowInventoryProduct,
        bool $highlightLowInventoryCalled
    ) {
        if ($highlightLowInventoryCalled) {
            $this->entityFallbackResolver->expects($this->exactly(2))
                ->method('getFallbackValue')
                ->willReturnMap([
                    [$product, LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION, 1, $highlightLowInventory],
                    [$product, LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION, 1, $lowInventoryThreshold]
                ]);

            $this->doctrineHelper->expects($this->once())
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

    public function providerTestIsLowInventoryProduct(): array
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
        $this->entityFallbackResolver->expects($this->any())
            ->method('getFallbackValue')
            ->with($this->logicalOr($products[0]['product'], $products[1]['product']), $this->logicalOr(
                $this->equalTo(LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION),
                $this->equalTo(LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION)
            ))
            ->willReturnCallback(function (
                Product $product,
                $option
            ) use (
                $highlightLowInventory,
                $lowInventoryThreshold
            ) {
                if ($option === LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION) {
                    return $highlightLowInventory[$product->getId()];
                }
                if ($option === LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION) {
                    return $lowInventoryThreshold[$product->getId()];
                }
            });

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->inventoryLevelRepository);

        $this->inventoryLevelRepository->expects($this->once())
            ->method('getQuantityForProductCollection')
            ->willReturn($quantityForProductCollection);

        $isLowInventoryProduct = $this->lowInventoryProvider->isLowInventoryCollection($products);

        $this->assertEquals($expectedResult, $isLowInventoryProduct);
    }

    private function getTestProducts(string $productUnitCode): array
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

    private function getQuantityForProductCollection(float $itemQuantity, float $setQuantity): array
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

    public function providerTestIsLowInventoryCollection(): array
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

    private function getInventoryLevel(int $quantity): InventoryLevel
    {
        $inventoryLevel = new InventoryLevel();
        $inventoryLevel->setQuantity($quantity);

        return $inventoryLevel;
    }

    protected function getProductEntity(int $id = null, bool $withPrimaryUnitPrecision = true): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        $unitPrecision = new ProductUnitPrecision();
        $productUnit = $this->getProductUnitEntity('item');
        $unitPrecision->setUnit($productUnit);

        if ($withPrimaryUnitPrecision) {
            $product->setPrimaryUnitPrecision($unitPrecision);
        }

        return $product;
    }

    private function getProductUnitEntity(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }
}
