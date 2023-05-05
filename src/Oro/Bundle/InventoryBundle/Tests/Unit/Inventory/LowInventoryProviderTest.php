<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
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
    private $entityFallbackResolver;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LowInventoryProvider */
    private $lowInventoryProvider;

    protected function setUp(): void
    {
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->lowInventoryProvider = new LowInventoryProvider(
            $this->entityFallbackResolver,
            $this->doctrine
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
        float $inventoryLevelQuantity,
        bool $expectedIsLowInventoryProduct,
        bool $highlightLowInventoryCalled
    ): void {
        if ($highlightLowInventoryCalled) {
            $this->entityFallbackResolver->expects($this->exactly(2))
                ->method('getFallbackValue')
                ->willReturnMap([
                    [$product, LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION, 1, $highlightLowInventory],
                    [$product, LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION, 1, $lowInventoryThreshold]
                ]);

            $inventoryLevel = $this->getInventoryLevel($inventoryLevelQuantity);

            $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
            $inventoryLevelRepository->expects($this->once())
                ->method('getLevelByProductAndProductUnit')
                ->willReturn($inventoryLevel);
            $this->doctrine->expects($this->once())
                ->method('getRepository')
                ->with(InventoryLevel::class)
                ->willReturn($inventoryLevelRepository);
        }

        $isLowInventoryProduct = $this->lowInventoryProvider->isLowInventoryProduct($product, $productUnit);

        $this->assertEquals($expectedIsLowInventoryProduct, $isLowInventoryProduct);
    }

    public function providerTestIsLowInventoryProduct(): array
    {
        return [
            'is low inventory: product and product unit' => [
                'product' => $this->getProduct(),
                'productUnit' => new ProductUnit(),
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 5.0,
                'expectedIsLowInventoryProduct' => true,
                'highlightLowInventoryCalled' => true
            ],
            'is not low inventory: product and product unit' => [
                'product' => $this->getProduct(),
                'productUnit' => new ProductUnit(),
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 15.0,
                'expectedIsLowInventoryProduct' => false,
                'highlightLowInventoryCalled' => true
            ],
            'is low inventory: product' => [
                'product' => $this->getProduct(),
                'productUnit' => null,
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 5.0,
                'expectedIsLowInventoryProduct' => true,
                'highlightLowInventoryCalled' => true
            ],
            'is not low inventory: product ' => [
                'product' => $this->getProduct(),
                'productUnit' => null,
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 15.0,
                'expectedIsLowInventoryProduct' => false,
                'highlightLowInventoryCalled' => true
            ],
            'is low inventory: product without primary unit' => [
                'product' => $this->getProduct(55, false),
                'productUnit' => null,
                'highlightLowInventory' => true,
                'lowInventoryThreshold' => 10,
                'inventoryLevelQuantity' => 15.0,
                'expectedIsLowInventoryProduct' => false,
                'highlightLowInventoryCalled' => false
            ]
        ];
    }

    /**
     * @dataProvider providerTestIsLowInventoryCollection
     */
    public function testIsLowInventoryCollection(
        array $products,
        array $highlightLowInventory,
        array $lowInventoryThreshold,
        array $quantityForProductCollection,
        array $expectedResult
    ): void {
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

                return null;
            });

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects($this->once())
            ->method('getQuantityForProductCollection')
            ->willReturn($quantityForProductCollection);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $isLowInventoryProduct = $this->lowInventoryProvider->isLowInventoryCollection($products);

        $this->assertEquals($expectedResult, $isLowInventoryProduct);
    }

    public function providerTestIsLowInventoryCollection(): array
    {
        return [
            '1. show low inventory for all products with enabled highlightLowInventory 
            (quantity < lowInventoryThreshold)' => [
                'products' => $this->getTestProducts('set'),
                'highlightLowInventory' => [1 => true, 2 => true],
                'lowInventoryThreshold' => [1 => 10, 2 => 10],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(10, 5),
                'expectedResult' => [1 => true, 2 => true],
            ],
            '2. hide low inventory for all products with enabled highlightLowInventory
            quantity > lowInventoryThreshold' => [
                'products' => $this->getTestProducts('set'),
                'highlightLowInventory' => [1 => true, 2 => true],
                'lowInventoryThreshold' => [1 => 10, 2 => 10],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(15, 30),
                'expectedResult' => [1 => false, 2 => false],
            ],
            '3. hide low inventory for all products with disabled highlightLowInventory' => [
                'products' => $this->getTestProducts('set'),
                'highlightLowInventory' => [1 => false, 2 => false],
                'lowInventoryThreshold' => [1 => 10, 2 => 10],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(15, 30),
                'expectedResult' => [1 => false, 2 => false],
            ],
            '4. show low inventory for product with enabled highlightLowInventory
            and hide product with disabled highlightLowInventory (quantity < lowInventoryThreshold)' => [
                'products' => $this->getTestProducts('item'),
                'highlightLowInventory' => [1 => true, 2 => false],
                'lowInventoryThreshold' => [1 => 10, 2 => 10],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(5, 30),
                'expectedResult' => [1 => true, 2 => false],
            ],
            '5. hide low inventory for product with enabled highlightLowInventory
            and hide product with disabled highlightLowInventory (quantity > lowInventoryThreshold)' => [
                'products' => $this->getTestProducts('item'),
                'highlightLowInventory' => [1 => true, 2 => false],
                'lowInventoryThreshold' => [1 => 10, 2 => 10],
                'quantityForProductCollection' => $this->getQuantityForProductCollection(15, 30),
                'expectedResult' => [1 => false, 2 => false],
            ],
        ];
    }

    private function getTestProducts(string $productUnitCode): array
    {
        return [
            ['product' => $this->getProduct(1), 'product_unit' => $this->getProductUnit($productUnitCode)],
            ['product' => $this->getProduct(2), 'product_unit' => $this->getProductUnit($productUnitCode)],
        ];
    }

    private function getQuantityForProductCollection(float $itemQuantity, float $setQuantity): array
    {
        return [
            ['product_id' => 1, 'code' => 'item', 'quantity' => $itemQuantity],
            ['product_id' => 1, 'code' => 'set', 'quantity' => $setQuantity],
            ['product_id' => 2, 'code' => 'item', 'quantity' => $itemQuantity],
            ['product_id' => 2, 'code' => 'set', 'quantity' => $setQuantity],
        ];
    }

    private function getInventoryLevel(int $quantity): InventoryLevel
    {
        $inventoryLevel = new InventoryLevel();
        $inventoryLevel->setQuantity($quantity);

        return $inventoryLevel;
    }

    private function getProduct(int $id = null, bool $withPrimaryUnitPrecision = true): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        $unitPrecision = new ProductUnitPrecision();
        $productUnit = $this->getProductUnit('item');
        $unitPrecision->setUnit($productUnit);

        if ($withPrimaryUnitPrecision) {
            $product->setPrimaryUnitPrecision($unitPrecision);
        }

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }
}
