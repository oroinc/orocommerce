<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductVariantProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Layout\Tests\Unit\Stubs\DataAccessorStub;

class ProductVariantProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVariantAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $availabilityProvider;

    /** @var ProductVariantProvider */
    private $productVariantProvider;

    protected function setUp(): void
    {
        $this->availabilityProvider = $this->createMock(ProductVariantAvailabilityProvider::class);

        $this->productVariantProvider = new ProductVariantProvider($this->availabilityProvider);
    }

    public function testHasProductAnyAvailableVariantReturnFalse(): void
    {
        $product = new Product();
        $this->availabilityProvider->expects($this->once())
            ->method('hasSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn(false);

        $result = $this->productVariantProvider->hasProductAnyAvailableVariant($product);
        $this->assertFalse($result);
    }

    public function testHasProductAnyAvailableVariantReturnTrue(): void
    {
        $product = new Product();
        $this->availabilityProvider->expects($this->once())
            ->method('hasSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn(true);

        $result = $this->productVariantProvider->hasProductAnyAvailableVariant($product);
        $this->assertTrue($result);
    }

    /**
     * @dataProvider getProductVariantOrProductDataProvider
     */
    public function testGetProductVariantOrProduct(array $data, ?Product $expectedProduct): void
    {
        $dataAccessor = new DataAccessorStub($data);
        $this->assertEquals($expectedProduct, $this->productVariantProvider->getProductVariantOrProduct($dataAccessor));
    }

    public function getProductVariantOrProductDataProvider(): array
    {
        $product = (new ProductStub())->setSku('Product');
        $productVariant = (new ProductStub())->setSku('Product Variant');
        $chosenProductVariant = (new ProductStub())->setSku('Chosen Product Variant');

        return [
            'product offset exists' => [
                'data' => ['product' => $product],
                'expectedProduct' => $product
            ],
            'product variant offset exists' => [
                'data' => ['product' => $product, 'productVariant' => $productVariant],
                'expectedProduct' => $productVariant
            ],
            'chosen product variant offset exists' => [
                'data' => ['product' => $product, 'productVariant' => $productVariant, 'chosenProductVariant' => null],
                'expectedProduct' => $productVariant
            ],
            'chosen product variant exists' => [
                'data' => [
                    'product' => $product,
                    'productVariant' => $productVariant,
                    'chosenProductVariant' => $chosenProductVariant
                ],
                'expectedProduct' => $chosenProductVariant
            ],
        ];
    }

    public function testGetProductVariantOrProductWhenNoData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not find product variant or product in layout update data');

        $dataAccessor = new DataAccessorStub([]);
        $this->productVariantProvider->getProductVariantOrProduct($dataAccessor);
    }
}
