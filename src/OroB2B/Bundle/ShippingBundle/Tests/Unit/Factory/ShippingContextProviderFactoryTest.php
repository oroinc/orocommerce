<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Factory;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShippingContextProviderFactoryTest extends \PHPUnit_Framework_TestCase
{

    /** @var  Checkout|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkout;

    /** @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var  ShoppingList|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingList;

    protected function setUp()
    {
        $this->checkout = $this->getMockBuilder('OroB2B\Bundle\CheckoutBundle\Entity\Checkout')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shoppingList = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new ShippingContextProviderFactory($this->checkout);
    }

    protected function tearDown()
    {
        unset($this->factory, $this->checkout);
    }


    public function testCreate()
    {
        $context = [
            'checkout'       => $this->checkout,
            'billingAddress' => 'address1',
            'currency'       => 'USD',
            'line_items'     => 'some line items'
        ];
        $this->checkout
            ->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($context['billingAddress']);
        $this->checkout
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($context['currency']);
        $this->checkout
            ->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($this->shoppingList);
        $this->shoppingList
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn(
                $context['line_items']
            );
        $this->assertEquals(new ShippingContextProvider($context), $this->factory->create($this->checkout));
    }

    public function testCreateWithoutSourceEntity()
    {
        $context = [
            'checkout'       => $this->checkout,
            'billingAddress' => 'address2',
            'currency'       => 'EUR',
        ];
        $this->checkout
            ->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($context['billingAddress']);
        $this->checkout
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($context['currency']);
        $this->checkout
            ->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn(null);
        $this->assertEquals(new ShippingContextProvider($context), $this->factory->create($this->checkout));
    }
}
