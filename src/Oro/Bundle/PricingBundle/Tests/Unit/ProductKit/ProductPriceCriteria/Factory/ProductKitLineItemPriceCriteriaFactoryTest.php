<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria\Factory;

use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder\ProductKitPriceCriteriaBuilderInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Factory\ProductKitLineItemPriceCriteriaBuilderFactory;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Factory\ProductKitLineItemPriceCriteriaFactory;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemPriceCriteriaFactoryTest extends TestCase
{
    private const USD = 'USD';

    private ProductKitLineItemPriceCriteriaBuilderFactory $builderFactory;

    private ProductKitLineItemPriceCriteriaFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->builderFactory = $this->createMock(ProductKitLineItemPriceCriteriaBuilderFactory::class);
        $this->factory = new ProductKitLineItemPriceCriteriaFactory($this->builderFactory);
    }

    /**
     * @dataProvider isSupportedDataProvider
     */
    public function testIsSupported(ProductLineItemInterface $lineItem, bool $expected): void
    {
        self::assertSame($expected, $this->factory->isSupported($lineItem, null));
    }

    public function isSupportedDataProvider(): array
    {
        return [
            'not supported - not ProductKitItemLineItemsAwareInterface' => [
                'lineItem' => $this->createMock(ProductLineItemInterface::class),
                'expected' => false,
            ],
            'not supported - not kit' => [
                'lineItem' => (new ProductKitItemLineItemsAwareStub(42))
                    ->setProduct(new Product())
                    ->setUnit(new ProductUnit())
                    ->setQuantity(12.3456),
                'expected' => false,
            ],
            'not supported - no product unit' => [
                'lineItem' => (new ProductKitItemLineItemsAwareStub(42))
                    ->setProduct((new Product())->setType(Product::TYPE_KIT))
                    ->setQuantity(12.3456),
                'expected' => false,
            ],
            'not supported - no quantity' => [
                'lineItem' => (new ProductKitItemLineItemsAwareStub(42))
                    ->setProduct((new Product())->setType(Product::TYPE_KIT))
                    ->setUnit(new ProductUnit())
                    ->setQuantity(null),
                'expected' => false,
            ],
            'not supported - negative quantity' => [
                'lineItem' => (new ProductKitItemLineItemsAwareStub(42))
                    ->setProduct((new Product())->setType(Product::TYPE_KIT))
                    ->setUnit(new ProductUnit())
                    ->setQuantity(-1.0),
                'expected' => false,
            ],
            'supported' => [
                'lineItem' => (new ProductKitItemLineItemsAwareStub(42))
                    ->setProduct((new Product())->setType(Product::TYPE_KIT))
                    ->setUnit(new ProductUnit())
                    ->setQuantity(12.3456),
                'expected' => true,
            ],
        ];
    }

    public function testCreateFromProductLineItemWhenNotSupported(): void
    {
        self::assertNull(
            $this->factory->createFromProductLineItem($this->createMock(ProductLineItemInterface::class), null)
        );
    }

    public function testCreateFromProductLineItemWhenSupported(): void
    {
        $productKit = (new ProductStub())->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit(new ProductUnit());

        $builder = $this->createMock(ProductKitPriceCriteriaBuilderInterface::class);
        $this->builderFactory
            ->expects(self::once())
            ->method('createFromProductLineItem')
            ->with($kitLineItem)
            ->willReturn($builder);

        $productKitPriceCriteria = $this->createMock(ProductKitPriceCriteria::class);
        $builder
            ->expects(self::once())
            ->method('create')
            ->willReturn($productKitPriceCriteria);

        self::assertSame($productKitPriceCriteria, $this->factory->createFromProductLineItem($kitLineItem, self::USD));
    }
}
