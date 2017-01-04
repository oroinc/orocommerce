<?php

namespace Oro\Bundle\CheckoutBundle\Bundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutPaymentContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var CheckoutPaymentContextFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var  ShoppingList|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingList;

    /** @var  CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutLineItemsManager;

    /** @var  TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProcessorProvider;


    /** @var  OrderPaymentLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentLineItemConverter;

    /**
     * @var PaymentContextBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextBuilderMock;

    /**
     * @var PaymentContextBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContextBuilderFactoryMock;

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

        $this->contextBuilderMock = $this->createMock(PaymentContextBuilderInterface::class);

        $this->paymentLineItemConverter = $this->createMock(OrderPaymentLineItemConverterInterface::class);

        $this->paymentContextBuilderFactoryMock = $this->createMock(PaymentContextBuilderFactoryInterface::class);

        $this->factory = new CheckoutPaymentContextFactory(
            $this->checkoutLineItemsManager,
            $this->totalProcessorProvider,
            $this->paymentLineItemConverter,
            $this->paymentContextBuilderFactoryMock
        );
    }

    public function testCreate()
    {
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $shippingMethod = 'SomeShippingMethod';
        $amount = 100;
        $customer = new Account();
        $customerUser = new AccountUser();
        $checkoutLineItems = new ArrayCollection([
            new OrderLineItem()
        ]);
        $convertedLineItems = new DoctrinePaymentLineItemCollection([
            new PaymentLineItem([])
        ]);

        $subtotal = (new Subtotal())
            ->setAmount($amount)
            ->setCurrency($currency);

        $checkout = (new Checkout())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setShippingMethod($shippingMethod)
            ->setAccount($customer)
            ->setAccountUser($customerUser);

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
            ->method('setShippingMethod')
            ->with($shippingMethod);

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

        $this->paymentContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createPaymentContextBuilder')
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

        $this->paymentLineItemConverter
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
        $shippingMethod = 'SomeShippingMethod';
        $amount = 100;
        $customer = new Account();
        $customerUser = new AccountUser();
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
            ->setShippingMethod($shippingMethod)
            ->setAccount($customer)
            ->setAccountUser($customerUser);

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
            ->method('setShippingMethod')
            ->with($shippingMethod);

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

        $this->paymentContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createPaymentContextBuilder')
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

        $this->paymentLineItemConverter
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
