<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Provider\FrontendProductUnitsProvider;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class FrontendProductUnitsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductUnitRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    private SingleUnitModeServiceInterface|\PHPUnit\Framework\MockObject\MockObject $singleUnitModeService;

    /** @var FrontendProductUnitsProvider */
    private $productUnitsProvider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductUnitRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(ProductUnit::class)
            ->willReturn($this->repository);

        $this->singleUnitModeService = $this->createMock(SingleUnitModeServiceInterface::class);

        $this->productUnitsProvider = new FrontendProductUnitsProvider($doctrine);
        $this->productUnitsProvider->setSingleUnitModeService($this->singleUnitModeService);
    }

    public function testGetUnitsForProductsWhenNoProductIds(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The list of product IDs must not be empty.');

        $this->productUnitsProvider->getUnitsForProducts([]);
    }

    public function testGetUnitsForProducts(): void
    {
        $productIds = [1, 2, 3];

        $this->repository->expects(self::once())
            ->method('getProductsUnitsByProductIds')
            ->with($productIds)
            ->willReturn([1 => ['kg'], 3 => ['box', 'bottle']]);

        self::assertSame(
            [1 => ['kg'], 2 => [], 3 => ['box', 'bottle']],
            $this->productUnitsProvider->getUnitsForProducts($productIds)
        );
        // test memory cache
        self::assertSame(
            [1 => ['kg'], 2 => [], 3 => ['box', 'bottle']],
            $this->productUnitsProvider->getUnitsForProducts($productIds)
        );
    }

    public function testGetUnitsForProductsWhenSomeDataAreAlreadyLoaded(): void
    {
        $this->repository->expects(self::exactly(2))
            ->method('getProductsUnitsByProductIds')
            ->willReturnMap([
                [[1, 2, 3], [1 => ['kg'], 3 => ['box', 'bottle']]],
                [[4, 5], [5 => ['liter']]],
            ]);

        self::assertSame(
            [1 => ['kg'], 2 => [], 3 => ['box', 'bottle']],
            $this->productUnitsProvider->getUnitsForProducts([1, 2, 3])
        );
        self::assertSame(
            [1 => ['kg'], 2 => [], 4 => [], 5 => ['liter']],
            $this->productUnitsProvider->getUnitsForProducts([1, 2, 4, 5])
        );
        self::assertSame(
            [5 => ['liter'], 1 => ['kg'], 4 => []],
            $this->productUnitsProvider->getUnitsForProducts([5, 1, 4])
        );
    }

    public function testGetUnitsForProductReturnsSellUnitsPrecisionWhenNotSingleUnitMode(): void
    {
        $this->singleUnitModeService->method('isSingleUnitMode')->willReturn(false);

        $each = new ProductUnit();
        $each->setCode('each');
        $set = new ProductUnit();
        $set->setCode('set');

        $p1 = (new ProductUnitPrecision())->setUnit($each)->setPrecision(0);
        $p2 = (new ProductUnitPrecision())->setUnit($set)->setPrecision(1);

        $product = new ProductStub();
        $product->addUnitPrecision($p1);
        $product->addUnitPrecision($p2);

        self::assertSame(['each' => 0, 'set' => 1], $this->productUnitsProvider->getUnitsForProduct($product));
    }

    public function testGetUnitsForProductReturnsPrimaryUnitInSingleUnitMode(): void
    {
        $this->singleUnitModeService->method('isSingleUnitMode')->willReturn(true);

        $each = new ProductUnit();
        $each->setCode('each');
        $set = new ProductUnit();
        $set->setCode('set');

        $primary = (new ProductUnitPrecision())->setUnit($each)->setPrecision(0)->setSell(true);
        $secondary = (new ProductUnitPrecision())->setUnit($set)->setPrecision(1)->setSell(true);

        $product = new ProductStub();
        $product->addUnitPrecision($primary);
        $product->addUnitPrecision($secondary);
        $product->setPrimaryUnitPrecision($primary);

        self::assertSame(['each' => 0], $this->productUnitsProvider->getUnitsForProduct($product));
    }
}
