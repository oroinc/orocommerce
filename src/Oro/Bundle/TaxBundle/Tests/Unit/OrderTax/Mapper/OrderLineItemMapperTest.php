<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\Mapper;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Oro\Bundle\TaxBundle\OrderTax\Mapper\OrderLineItemMapper;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;

class OrderLineItemMapperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ITEM_ID = 123;
    const ITEM_PRICE_VALUE = 12.34;
    const ITEM_QUANTITY = 12;

    const CONTEXT_KEY = 'context_key';
    const CONTEXT_VALUE = 'context_value';

    /**
     * @var OrderLineItemMapper
     */
    protected $mapper;

    /**
     * @var TaxationAddressProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressProvider;

    /**
     * @var ContextEventDispatcher
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->addressProvider = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Event\ContextEventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->willReturn(new \ArrayObject([self::CONTEXT_KEY => self::CONTEXT_VALUE]));

        $this->mapper = new OrderLineItemMapper(
            $this->eventDispatcher,
            $this->addressProvider,
            'Oro\Bundle\OrderBundle\Entity\OrderLineItem'
        );
    }

    protected function tearDown()
    {
        unset($this->mapper);
    }

    public function testGetProcessingClassName()
    {
        $this->assertEquals('Oro\Bundle\OrderBundle\Entity\OrderLineItem', $this->mapper->getProcessingClassName());
    }

    public function testMap()
    {
        $lineItem = $this->createLineItem(self::ITEM_ID, self::ITEM_QUANTITY, self::ITEM_PRICE_VALUE);

        $taxable = $this->mapper->map($lineItem);

        $this->assertTaxable($taxable, self::ITEM_ID, self::ITEM_QUANTITY, self::ITEM_PRICE_VALUE);
    }

    public function testMapWithoutPrice()
    {
        $lineItem = $this->createLineItem(self::ITEM_ID, self::ITEM_QUANTITY);

        $taxable = $this->mapper->map($lineItem);

        $this->assertTaxable($taxable, self::ITEM_ID, self::ITEM_QUANTITY, null);
    }

    /**
     * @param int $id
     * @param int $quantity
     * @param float $priceValue
     * @return OrderLineItem
     */
    protected function createLineItem($id, $quantity, $priceValue = null)
    {
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getEntity('Oro\Bundle\OrderBundle\Entity\OrderLineItem', ['id' => $id]);
        $lineItem
            ->setQuantity($quantity)
            ->setOrder(new Order())
            ->setValue($priceValue)
            ->setCurrency('USD')
            ->setPrice(Price::create($priceValue, 'USD'));

        return $lineItem;
    }

    /**
     * @param Taxable $taxable
     * @param int $id
     * @param int $quantity
     * @param float $priceValue
     */
    protected function assertTaxable($taxable, $id, $quantity, $priceValue)
    {
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Taxable', $taxable);
        $this->assertEquals($id, $taxable->getIdentifier());
        $this->assertEquals($quantity, $taxable->getQuantity());
        $this->assertEquals($priceValue, $taxable->getPrice());
        $this->assertEquals(0, $taxable->getAmount());
        $this->assertEquals(self::CONTEXT_VALUE, $taxable->getContextValue(self::CONTEXT_KEY));
        $this->assertEmpty($taxable->getItems());
    }
}
