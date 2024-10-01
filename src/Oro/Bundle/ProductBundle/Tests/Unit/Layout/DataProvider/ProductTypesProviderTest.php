<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductTypesProvider;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use PHPUnit\Framework\MockObject\MockObject;

class ProductTypesProviderTest extends \PHPUnit\Framework\TestCase
{
    private ProductTypeProvider|MockObject $productTypeProvider;

    private ProductTypesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->productTypeProvider = $this->createMock(ProductTypeProvider::class);
        $this->provider = new ProductTypesProvider($this->productTypeProvider);
    }

    /**
     * @dataProvider productTypesDataProvider
     */
    public function testIsProductTypeEnabled(bool $expected, string $productType): void
    {
        $this->productTypeProvider->expects(self::once())
            ->method('getAvailableProductTypes')
            ->willReturn([
                'oro.product.type.kit' => 'kit',
                'oro.product.type.simple' => 'simple',
            ]);

        self::assertSame($expected, $this->provider->isProductTypeEnabled($productType));
    }

    public function productTypesDataProvider(): \Generator
    {
        yield 'enabled' => [
            'expected' => true,
            'productType' => 'kit',
        ];
        yield 'not enabled' => [
            'expected' => false,
            'productType' => 'configurable',
        ];
        yield 'not available' => [
            'expected' => false,
            'productType' => 'oro.product.type.kit',
        ];
    }
}
