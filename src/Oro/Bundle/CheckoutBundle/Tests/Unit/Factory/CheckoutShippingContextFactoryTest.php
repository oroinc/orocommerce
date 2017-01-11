<?php

namespace Oro\Bundle\CheckoutBundle\Bundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutShippingContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var CheckoutShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var  ShoppingList|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingList;

    /** @var  CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutLineItemsManager;

    /** @var  TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProcessorProvider;


    /** @var  OrderShippingLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingLineItemConverter;

    /**
     * @var ShippingContextBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextBuilderMock;

    /**
     * @var ShippingContextBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingContextBuilderFactoryMock;

    protected function setUp()
    {
        $this->shoppingList = $this->getMockBuilder(ShoppingList::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutLineItemsManager = $this->getMockBuilder(CheckoutLineItemsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessorProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextBuilderMock = $this->createMock(ShippingContextBuilderInterface::class);

        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);

        $this->shippingContextBuilderFactoryMock = $this->createMock(ShippingContextBuilderFactoryInterface::class);

        $this->factory = new CheckoutShippingContextFactory(
            $this->checkoutLineItemsManager,
            $this->totalProcessorProvider,
            $this->shippingLineItemConverter,
            $this->shippingContextBuilderFactoryMock
        );
    }

    public function testCreate()
    {
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $paymentMethod = 'SomePaymentMethod';
        $amount = 100;
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $checkoutLineItems = new ArrayCollection([
            new OrderLineItem()
        ]);
        $convertedLineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([])
        ]);

        $subtotal = (new Subtotal())
            ->setAmount($amount)
            ->setCurrency($currency);

        $checkout = (new Checkout())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setPaymentMethod($paymentMethod)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser);

        $this->contextBuilderMock
            ->method('setShippingAddress')
            ->with($address);

        $this->contextBuilderMock
            ->method('setBillingAddress')
            ->with($address);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setLineItems')
            ->with($convertedLineItems);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setPaymentMethod')
            ->with($paymentMethod);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setCustomer')
            ->with($customer);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('getResult');

        $this->shippingContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with(
                $checkout->getCurrency(),
                Price::create($subtotal->getAmount(), $subtotal->getCurrency()),
                $checkout,
                (string)$checkout->getId()
            )
            ->willReturn($this->contextBuilderMock);

        $this->checkoutLineItemsManager
            ->expects(static::once())
            ->method('getData')
            ->willReturn($checkoutLineItems);

        $this->shippingLineItemConverter
            ->expects($this->once())
            ->method('convertLineItems')
            ->willReturn($convertedLineItems);

        $this->totalProcessorProvider
            ->expects(static::once())
            ->method('getTotal')
            ->with($checkout)
            ->willReturn($subtotal);

        $this->factory->create($checkout);
    }

    public function testWithNullLineItems()
    {
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $paymentMethod = 'SomePaymentMethod';
        $amount = 100;
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $checkoutLineItems = new ArrayCollection([
            new OrderLineItem()
        ]);

        $subtotal = (new Subtotal())
            ->setAmount($amount)
            ->setCurrency($currency);

        $checkout = (new Checkout())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setPaymentMethod($paymentMethod)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser);

        $this->contextBuilderMock
            ->method('setShippingAddress')
            ->with($address);

        $this->contextBuilderMock
            ->method('setBillingAddress')
            ->with($address);

        $this->contextBuilderMock
            ->expects($this->never())
            ->method('setLineItems');

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setPaymentMethod')
            ->with($paymentMethod);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setCustomer')
            ->with($customer);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('getResult');

        $this->shippingContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with(
                $checkout->getCurrency(),
                Price::create($subtotal->getAmount(), $subtotal->getCurrency()),
                $checkout,
                (string)$checkout->getId()
            )
            ->willReturn($this->contextBuilderMock);

        $this->checkoutLineItemsManager
            ->expects(static::once())
            ->method('getData')
            ->willReturn($checkoutLineItems);

        $this->shippingLineItemConverter
            ->expects($this->once())
            ->method('convertLineItems')
            ->willReturn(null);

        $this->totalProcessorProvider
            ->expects(static::once())
            ->method('getTotal')
            ->with($checkout)
            ->willReturn($subtotal);

        $this->factory->create($checkout);
    }
}
