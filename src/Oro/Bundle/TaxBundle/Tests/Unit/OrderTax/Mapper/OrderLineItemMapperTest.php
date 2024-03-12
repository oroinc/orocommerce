<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\Mapper;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\OrderTax\Mapper\OrderLineItemMapper;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLineItemMapperTest extends TestCase
{
    use EntityTrait;

    private const ITEM_ID = 123;
    private const ITEM_PRICE_VALUE = 12.34;
    private const ITEM_QUANTITY = 12;

    private const CONTEXT_KEY = 'context_key';
    private const CONTEXT_VALUE = 'context_value';

    private TaxationAddressProvider|MockObject $addressProvider;
    private OrderLineItemMapper $mapper;

    protected function setUp(): void
    {
        $this->addressProvider = $this->createMock(TaxationAddressProvider::class);

        $eventDispatcher = $this->createMock(ContextEventDispatcher::class);
        $eventDispatcher->expects($this->any())
            ->method('dispatch')
            ->willReturn(new \ArrayObject([self::CONTEXT_KEY => self::CONTEXT_VALUE]));

        $this->mapper = new OrderLineItemMapper(
            $eventDispatcher,
            $this->addressProvider
        );
    }

    public function testMap(): void
    {
        $lineItem = $this->createLineItem(self::ITEM_ID, self::ITEM_QUANTITY, self::ITEM_PRICE_VALUE);

        $taxable = $this->mapper->map($lineItem);

        $this->assertTaxable(
            $taxable,
            self::ITEM_ID,
            BigDecimal::of(self::ITEM_QUANTITY),
            BigDecimal::of(self::ITEM_PRICE_VALUE)
        );
    }

    public function testMapWithSimpleProduct(): void
    {
        $lineItem = $this->createLineItem(self::ITEM_ID, self::ITEM_QUANTITY);

        $taxable = $this->mapper->map($lineItem);

        $this->assertTaxable($taxable, self::ITEM_ID, BigDecimal::of(self::ITEM_QUANTITY), BigDecimal::one());
    }

    public function testMapWithKitProductAndWithoutKitLineItems(): void
    {
        $productKit = $this->getEntity(Product::class, ['id' => 1]);
        $productKit->setType(Product::TYPE_KIT);

        $lineItem = $this->getEntity(OrderLineItem::class, ['id' => self::ITEM_ID]);
        $lineItem
            ->setOrder(new Order())
            ->setProduct($productKit);

        $taxable = $this->mapper->map($lineItem);

        self::assertTrue($taxable->isKitTaxable());
        self::assertEquals(BigDecimal::zero(), $taxable->getPrice());
    }

    public function testMapWithKitProduct(): void
    {
        $kitItem1 = new OrderProductKitItemLineItem();
        $kitItem1->setPrice(Price::create(100, 'USD'));
        $kitItem1->setQuantity(2);
        $kitItem2 = new OrderProductKitItemLineItem();
        $kitItem2->setPrice(Price::create(300, 'USD'));
        $kitItem2->setQuantity(1);

        $productKit = $this->getEntity(Product::class, ['id' => 1]);
        $productKit->setType(Product::TYPE_KIT);

        $lineItem = $this->getEntity(OrderLineItem::class, ['id' => self::ITEM_ID]);
        $lineItem
            ->setOrder(new Order())
            ->setProduct($productKit)
            ->setPrice(Price::create(1300, 'USD'))
            ->addKitItemLineItem($kitItem1)
            ->addKitItemLineItem($kitItem2);

        $taxable = $this->mapper->map($lineItem);

        self::assertTrue($taxable->isKitTaxable());
        self::assertEquals(BigDecimal::of('800'), $taxable->getPrice());

        $items = [];
        foreach ($taxable->getItems() as $item) {
            $items[] = $item;
        }

        self::assertFalse($items[0]->isKitTaxable());
        self::assertEquals(BigDecimal::of('100'), $items[0]->getPrice());

        self::assertFalse($items[1]->isKitTaxable());
        self::assertEquals(BigDecimal::of('300'), $items[1]->getPrice());
    }

    private function createLineItem(int $id, int $quantity, float $priceValue = 1): OrderLineItem
    {
        $lineItem = $this->getEntity(OrderLineItem::class, ['id' => $id]);
        $lineItem
            ->setQuantity($quantity)
            ->setOrder(new Order())
            ->setValue($priceValue)
            ->setCurrency('USD')
            ->setPrice(Price::create($priceValue, 'USD'));

        return $lineItem;
    }

    private function assertTaxable(Taxable $taxable, int $id, BigDecimal $quantity, BigDecimal $priceValue): void
    {
        $this->assertInstanceOf(Taxable::class, $taxable);
        $this->assertEquals($id, $taxable->getIdentifier());
        $this->assertEquals($quantity, $taxable->getQuantity());
        $this->assertEquals($priceValue, $taxable->getPrice());
        $this->assertEquals('0', $taxable->getAmount());
        $this->assertEquals(self::CONTEXT_VALUE, $taxable->getContextValue(self::CONTEXT_KEY));
        $this->assertEmpty($taxable->getItems());
        $this->assertFalse($taxable->isKitTaxable());
    }
}
