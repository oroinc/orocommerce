<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model\ProductLineItemPriceCriteriaFactory;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory\SimpleProductLineItemPriceCriteriaFactory;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class SimpleProductLineItemPriceCriteriaFactoryTest extends TestCase
{
    private const USD = 'USD';

    private ProductPriceCriteriaBuilderInterface $simpleProductPriceCriteriaBuilder;

    private SimpleProductLineItemPriceCriteriaFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->simpleProductPriceCriteriaBuilder = $this->createMock(ProductPriceCriteriaBuilderInterface::class);
        $this->factory = new SimpleProductLineItemPriceCriteriaFactory($this->simpleProductPriceCriteriaBuilder);
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
            'not supported - no product' => [
                'lineItem' => (new ProductLineItem(42))
                    ->setUnit(new ProductUnit())
                    ->setQuantity(12.3456),
                'expected' => false,
            ],
            'not supported - no product unit' => [
                'lineItem' => (new ProductLineItem(42))
                    ->setProduct(new Product())
                    ->setQuantity(12.3456),
                'expected' => false,
            ],
            'not supported - no quantity' => [
                'lineItem' => (new ProductLineItem(42))
                    ->setProduct(new Product())
                    ->setUnit(new ProductUnit())
                    ->setQuantity(null),
                'expected' => false,
            ],
            'not supported - negative quantity' => [
                'lineItem' => (new ProductLineItem(42))
                    ->setProduct(new Product())
                    ->setUnit(new ProductUnit())
                    ->setQuantity(-1.0),
                'expected' => false,
            ],
            'supported' => [
                'lineItem' => (new ProductLineItem(42))
                    ->setProduct(new Product())
                    ->setUnit(new ProductUnit())
                    ->setQuantity(12.3456),
                'expected' => true,
            ],
        ];
    }

    public function testCreateFromProductLineItem(): void
    {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $product = (new ProductStub())->setId(100);
        $lineItem = (new ProductLineItem(42))
            ->setProduct($product)
            ->setUnit($productUnitEach)
            ->setQuantity(111);

        $this->simpleProductPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProduct')
            ->with($product)
            ->willReturnSelf();

        $this->simpleProductPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProductUnit')
            ->with($productUnitEach)
            ->willReturnSelf();

        $this->simpleProductPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setQuantity')
            ->with($lineItem->getQuantity())
            ->willReturnSelf();

        $this->simpleProductPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setCurrency')
            ->with(self::USD)
            ->willReturnSelf();

        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $this->simpleProductPriceCriteriaBuilder
            ->expects(self::once())
            ->method('create')
            ->willReturn($productPriceCriteria);

        self::assertSame($productPriceCriteria, $this->factory->createFromProductLineItem($lineItem, self::USD));
    }
}
