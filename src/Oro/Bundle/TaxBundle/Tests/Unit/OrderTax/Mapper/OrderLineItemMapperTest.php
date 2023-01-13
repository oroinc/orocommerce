<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\Mapper;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\OrderTax\Mapper\OrderLineItemMapper;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderLineItemMapperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const ITEM_ID = 123;
    private const ITEM_PRICE_VALUE = 12.34;
    private const ITEM_QUANTITY = 12;

    private const CONTEXT_KEY = 'context_key';
    private const CONTEXT_VALUE = 'context_value';

    /** @var TaxationAddressProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $addressProvider;

    /** @var OrderLineItemMapper */
    private $mapper;

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

    public function testMap()
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

    public function testMapWithoutPrice()
    {
        $lineItem = $this->createLineItem(self::ITEM_ID, self::ITEM_QUANTITY);

        $taxable = $this->mapper->map($lineItem);

        $this->assertTaxable($taxable, self::ITEM_ID, BigDecimal::of(self::ITEM_QUANTITY), BigDecimal::one());
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
    }
}
