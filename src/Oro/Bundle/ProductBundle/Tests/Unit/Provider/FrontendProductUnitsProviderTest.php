<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Provider\FrontendProductUnitsProvider;

class FrontendProductUnitsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductUnitRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

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

        $this->productUnitsProvider = new FrontendProductUnitsProvider($doctrine);
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
}
