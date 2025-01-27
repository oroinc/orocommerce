<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Twig;

use Oro\Bundle\PricingBundle\Twig\ProductPriceExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPriceExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ProductPriceExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new ProductPriceExtension();
    }

    /**
     * @dataProvider productPriceProvider
     */
    public function testIsPriceHidden(bool $expected, mixed $product, bool $applicableForConfiguredProduct): void
    {
        self::assertSame(
            $expected,
            self::callTwigFunction($this->extension, 'is_price_hidden', [$product, $applicableForConfiguredProduct])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function productPriceProvider(): \Generator
    {
        yield [
            'expected' => false,
            'product' => $this->createProduct(Product::TYPE_SIMPLE),
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => false,
            'product' => $this->createProduct(Product::TYPE_SIMPLE),
            'applicableForConfiguredProduct' => false,
        ];
        yield [
            'expected' => true,
            'product' => $this->createProduct(Product::TYPE_KIT),
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => true,
            'product' => $this->createProduct(Product::TYPE_KIT),
            'applicableForConfiguredProduct' => false,
        ];
        yield [
            'expected' => false,
            'product' => $this->createProduct(Product::TYPE_CONFIGURABLE),
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => true,
            'product' => $this->createProduct(Product::TYPE_CONFIGURABLE),
            'applicableForConfiguredProduct' => false,
        ];

        yield [
            'expected' => false,
            'product' => $this->createProductViewMock(Product::TYPE_SIMPLE),
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => false,
            'product' => $this->createProductViewMock(Product::TYPE_SIMPLE),
            'applicableForConfiguredProduct' => false,
        ];
        yield [
            'expected' => true,
            'product' => $this->createProductViewMock(Product::TYPE_KIT),
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => true,
            'product' => $this->createProductViewMock(Product::TYPE_KIT),
            'applicableForConfiguredProduct' => false,
        ];
        yield [
            'expected' => false,
            'product' => $this->createProductViewMock(Product::TYPE_CONFIGURABLE),
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => true,
            'product' => $this->createProductViewMock(Product::TYPE_CONFIGURABLE),
            'applicableForConfiguredProduct' => false,
        ];

        yield [
            'expected' => false,
            'product' => ['type' => Product::TYPE_SIMPLE],
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => false,
            'product' => ['type' => Product::TYPE_SIMPLE],
            'applicableForConfiguredProduct' => false,
        ];
        yield [
            'expected' => true,
            'product' => ['type' => Product::TYPE_KIT],
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => true,
            'product' => ['type' => Product::TYPE_KIT],
            'applicableForConfiguredProduct' => false,
        ];
        yield [
            'expected' => false,
            'product' => ['type' => Product::TYPE_CONFIGURABLE],
            'applicableForConfiguredProduct' => true,
        ];
        yield [
            'expected' => true,
            'product' => ['type' => Product::TYPE_CONFIGURABLE],
            'applicableForConfiguredProduct' => false,
        ];

        yield [
            'expected' => true,
            'product' => null,
            'applicableForConfiguredProduct' => false,
        ];
        yield [
            'expected' => true,
            'product' => null,
            'applicableForConfiguredProduct' => true,
        ];
    }

    private function createProduct(string $type): Product
    {
        return (new ProductStub())
            ->setType($type);
    }

    private function createProductViewMock(string $type): ProductView|MockObject
    {
        $product = $this->createMock(ProductView::class);
        $product->expects(self::once())->method('has')->with('type')->willReturn(true);
        $product->expects(self::once())->method('get')->with('type')->willReturn($type);

        return $product;
    }
}
