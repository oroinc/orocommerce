<?php

namespace Oro\Bundle\CheckoutBundle\Bundle\Tests\Unit\Factory;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShippingContextProviderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var  ShoppingList|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingList;

    /** @var  CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutLineItemsManager;

    /** @var  TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProcessorProvider;

    /** @var  ShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingContextFactory;

    protected function setUp()
    {
        $this->shoppingList = $this->getMockBuilder('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutLineItemsManager = $this->getMockBuilder(CheckoutLineItemsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessorProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextFactory = $this->getMockBuilder(ShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new ShippingContextProviderFactory(
            $this->checkoutLineItemsManager,
            $this->totalProcessorProvider,
            $this->shippingContextFactory
        );
    }

    protected function tearDown()
    {
        unset(
            $this->factory,
            $this->checkout,
            $this->shoppingList,
            $this->checkoutLineItemsManager,
            $this->totalProcessorProvider,
            $this->shippingContextFactory
        );
    }


    public function testCreate()
    {
        $address = $this->getMock(OrderAddress::class);
        $currency = 'USD';
        $paymentMethod = 'SomePaymentMethod';
        $amount = 100;

        $subtotal = (new Subtotal())
            ->setAmount($amount)
            ->setCurrency($currency);

        $checkout = (new Checkout())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setPaymentMethod($paymentMethod);

        $context = new ShippingContext();
        $context->setBillingAddress($address);
        $context->setShippingAddress($address);
        $context->setCurrency($currency);
        $context->setPaymentMethod($paymentMethod);
        $context->setSubtotal(Price::create($amount, $currency));

        $this->checkoutLineItemsManager
            ->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->shippingContextFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new ShippingContext());


        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($checkout)
            ->willReturn($subtotal);

        $this->assertEquals($context, $this->factory->create($checkout));
    }
}
