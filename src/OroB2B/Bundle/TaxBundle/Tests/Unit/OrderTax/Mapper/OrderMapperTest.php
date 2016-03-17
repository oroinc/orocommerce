<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\OrderTax\Mapper;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Event\ContextEventDispatcher;
use OroB2B\Bundle\TaxBundle\OrderTax\Mapper\OrderLineItemMapper;
use OroB2B\Bundle\TaxBundle\OrderTax\Mapper\OrderMapper;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;

class OrderMapperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ORDER_ID = 123;
    const ORDER_SUBTOTAL = 234.34;

    const CONTEXT_KEY = 'context_key';
    const CONTEXT_VALUE = 'context_value';

    /**
     * @var OrderMapper
     */
    protected $mapper;

    /**
     * @var OrderLineItemMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderLineItemMapper;

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
        $this->orderLineItemMapper = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\OrderTax\Mapper\OrderLineItemMapper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider->expects($this->any())->method('getDestinationAddress')->willReturnArgument(0);
        $this->addressProvider->expects($this->any())->method('getTaxationAddress')->willReturnArgument(1);

        $this->eventDispatcher = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Event\ContextEventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->willReturn(new \ArrayObject([self::CONTEXT_KEY => self::CONTEXT_VALUE]));

        $this->mapper = new OrderMapper(
            $this->eventDispatcher,
            $this->addressProvider,
            'OroB2B\Bundle\OrderBundle\Entity\Order'
        );
        $this->mapper->setOrderLineItemMapper($this->orderLineItemMapper);
    }

    protected function tearDown()
    {
        unset($this->mapper, $this->orderLineItemMapper);
    }

    public function testGetProcessingClassName()
    {
        $this->assertEquals('OroB2B\Bundle\OrderBundle\Entity\Order', $this->mapper->getProcessingClassName());
    }

    public function testMap()
    {
        $this->orderLineItemMapper
            ->expects($this->once())
            ->method('map')
            ->willReturn(new Taxable());

        $order = $this->createOrder(self::ORDER_ID, self::ORDER_SUBTOTAL);

        $taxable = $this->mapper->map($order);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Taxable', $taxable);
        $this->assertEquals(self::ORDER_ID, $taxable->getIdentifier());
        $this->assertEquals(1, $taxable->getQuantity());
        $this->assertEquals(0, $taxable->getPrice());
        $this->assertEquals(self::ORDER_SUBTOTAL, $taxable->getAmount());
        $this->assertEquals($order->getShippingAddress(), $taxable->getTaxationAddress());
        $this->assertEquals($order->getBillingAddress(), $taxable->getDestination());
        $this->assertNull($taxable->getOrigin());
        $this->assertEquals(self::CONTEXT_VALUE, $taxable->getContextValue(self::CONTEXT_KEY));
        $this->assertNotEmpty($taxable->getItems());
        $this->assertCount(1, $taxable->getItems());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Taxable', $taxable->getItems()->current());
    }

    /**
     * Create order
     *
     * @param int $id
     * @param float $subtotal
     * @return Order
     */
    protected function createOrder($id, $subtotal)
    {
        $billingAddress = (new OrderAddress())
            ->setFirstName('FirstName')
            ->setLastName('LastName')
            ->setStreet('street');
        $shippingAddress = (new OrderAddress())
            ->setFirstName('FirstName')
            ->setLastName('LastName')
            ->setStreet('street');

        /** @var Order $order */
        $order = $this->getEntity('OroB2B\Bundle\OrderBundle\Entity\Order', ['id' => $id]);
        $order
            ->setSubtotal($subtotal)
            ->addLineItem(new OrderLineItem())
            ->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress);

        return $order;
    }
}
