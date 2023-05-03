<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository;
use Oro\Bundle\ProductBundle\Provider\ProductKitsByUnitPrecisionProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductUnitPrecisionStub;

class ProductKitsByUnitPrecisionProviderTest extends \PHPUnit\Framework\TestCase
{
    private ProductKitItemRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    protected ProductKitsByUnitPrecisionProvider $provider;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ProductKitItemRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ProductKitItem::class)
            ->willReturn($this->repository);

        $this->provider = new ProductKitsByUnitPrecisionProvider($managerRegistry);
    }

    public function testGetRelatedProductKitsSkuWhenNoId(): void
    {
        self::assertSame([], $this->provider->getRelatedProductKitsSku(new ProductUnitPrecision()));
    }

    public function testGetRelatedProductKitsSkuWhenNoProductKitSkus(): void
    {
        $unitPrecision = (new ProductUnitPrecisionStub(42));
        $this->repository
            ->expects(self::once())
            ->method('findProductKitsSkuByUnitPrecision')
            ->with($unitPrecision)
            ->willReturn([]);

        self::assertSame([], $this->provider->getRelatedProductKitsSku($unitPrecision));
    }

    /**
     * @dataProvider getRelatedProductKitsSkuDataProvider
     *
     * @param string[] $skus
     * @param string $ellipsis
     * @param string[] $expected
     * @return void
     */
    public function testGetRelatedProductKitsSku(array $skus, string $ellipsis, array $expected): void
    {
        $unitPrecision = (new ProductUnitPrecisionStub(42));
        $this->repository
            ->expects(self::once())
            ->method('findProductKitsSkuByUnitPrecision')
            ->with($unitPrecision)
            ->willReturn($skus);

        self::assertSame($expected, $this->provider->getRelatedProductKitsSku($unitPrecision, 3, $ellipsis));
    }

    public function getRelatedProductKitsSkuDataProvider(): array
    {
        return [
            '0 skus' => ['skus' => [], 'ellipsis' => '...', 'expected' => []],
            '1 sku' => ['skus' => ['sku1'], 'ellipsis' => '...', 'expected' => ['sku1']],
            '3 skus - equals to limit' => [
                'skus' => ['sku1', 'sku2', 'sku3'],
                'ellipsis' => '...',
                'expected' => ['sku1', 'sku2', 'sku3'],
            ],
            '4 skus - exceeds limit' => [
                'skus' => ['sku1', 'sku2', 'sku3', 'sku4'],
                'ellipsis' => '...',
                'expected' => ['sku1', 'sku2', 'sku3', '...'],
            ],
            '4 skus - exceeds limit with custom ellipsis' => [
                'skus' => ['sku1', 'sku2', 'sku3', 'sku4'],
                'ellipsis' => 'and more',
                'expected' => ['sku1', 'sku2', 'sku3', 'and more'],
            ],
            '4 skus - exceeds limit without ellipsis' => [
                'skus' => ['sku1', 'sku2', 'sku3', 'sku4'],
                'ellipsis' => '',
                'expected' => ['sku1', 'sku2', 'sku3', 'sku4'],
            ],
        ];
    }
}
