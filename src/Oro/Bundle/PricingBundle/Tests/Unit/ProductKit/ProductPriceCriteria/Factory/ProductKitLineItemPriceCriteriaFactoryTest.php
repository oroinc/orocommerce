<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\ProductKit\ProductPriceCriteria\Factory;

use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder\ProductKitPriceCriteriaBuilderInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Factory\ProductKitLineItemPriceCriteriaFactory;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemPriceCriteriaFactoryTest extends TestCase
{
    private const USD = 'USD';

    private ProductKitPriceCriteriaBuilderInterface $productKitPriceCriteriaBuilder;

    private ProductKitLineItemPriceCriteriaFactory $factory;

    protected function setUp(): void
    {
        $this->productKitPriceCriteriaBuilder = $this->createMock(ProductKitPriceCriteriaBuilderInterface::class);
        $this->factory = new ProductKitLineItemPriceCriteriaFactory($this->productKitPriceCriteriaBuilder);
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
                    ->setUnit(new ProductUnit()),
                'expected' => false,
            ],
            'not supported - no product unit' => [
                'lineItem' => (new ProductKitItemLineItemsAwareStub(42))->setProduct(new Product()),
                'expected' => false,
            ],
            'supported' => [
                'lineItem' => (new ProductKitItemLineItemsAwareStub(42))
                    ->setProduct((new Product())->setType(Product::TYPE_KIT))
                    ->setUnit(new ProductUnit()),
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
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem1Product = (new ProductStub())->setId(1);
        $kitItem1 = new ProductKitItemStub(11);
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(10))
            ->setKitItem($kitItem1)
            ->setProduct($kitItemLineItem1Product)
            ->setUnit($productUnitItem)
            ->setQuantity(11);
        $kitItemLineItem2Product = (new ProductStub())->setId(2);
        $kitItem2 = new ProductKitItemStub(22);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(20))
            ->setKitItem($kitItem2)
            ->setProduct($kitItemLineItem2Product)
            ->setUnit($productUnitEach)
            ->setQuantity(22);
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111)
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProduct')
            ->with($productKit)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProductUnit')
            ->with($productUnitEach)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setQuantity')
            ->with($kitLineItem->getQuantity())
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setCurrency')
            ->with(self::USD)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::exactly(2))
            ->method('addKitItemProduct')
            ->withConsecutive(
                [
                    $kitItemLineItem1->getKitItem(),
                    $kitItemLineItem1->getProduct(),
                    $kitItemLineItem1->getProductUnit(),
                    $kitItemLineItem1->getQuantity(),
                ],
                [
                    $kitItemLineItem2->getKitItem(),
                    $kitItemLineItem2->getProduct(),
                    $kitItemLineItem2->getProductUnit(),
                    $kitItemLineItem2->getQuantity(),
                ]
            )
            ->willReturnSelf();

        $productKitPriceCriteria = $this->createMock(ProductKitPriceCriteria::class);
        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('create')
            ->willReturn($productKitPriceCriteria);

        self::assertSame($productKitPriceCriteria, $this->factory->createFromProductLineItem($kitLineItem, self::USD));
    }

    public function testCreateFromProductLineItemWhenNoKitItemLineItems(): void
    {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productKit = (new ProductStub())->setId(100)->setType(Product::TYPE_KIT);
        $kitLineItem = (new ProductKitItemLineItemsAwareStub(42))
            ->setProduct($productKit)
            ->setUnit($productUnitEach)
            ->setQuantity(111);

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProduct')
            ->with($productKit)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setProductUnit')
            ->with($productUnitEach)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setQuantity')
            ->with($kitLineItem->getQuantity())
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('setCurrency')
            ->with(self::USD)
            ->willReturnSelf();

        $this->productKitPriceCriteriaBuilder
            ->expects(self::never())
            ->method('addKitItemProduct');

        $productKitPriceCriteria = $this->createMock(ProductKitPriceCriteria::class);
        $this->productKitPriceCriteriaBuilder
            ->expects(self::once())
            ->method('create')
            ->willReturn($productKitPriceCriteria);

        self::assertSame($productKitPriceCriteria, $this->factory->createFromProductLineItem($kitLineItem, self::USD));
    }
}
