<?php

namespace Oro\Bundle\CheckoutBundle\Bundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CheckoutPaymentContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var CheckoutPaymentContextFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutLineItemsManager;

    /** @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $totalProcessorProvider;

    /** @var OrderPaymentLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentLineItemConverter;

    /** @var PaymentContextBuilderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextBuilderMock;

    /** @var PaymentContextBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentContextBuilderFactoryMock;

    /** @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingOriginProvider;

    protected function setUp()
    {
        $this->checkoutLineItemsManager = $this->getMockBuilder(CheckoutLineItemsManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessorProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextBuilderMock = $this->createMock(PaymentContextBuilderInterface::class);

        $this->paymentLineItemConverter = $this->createMock(OrderPaymentLineItemConverterInterface::class);

        $this->paymentContextBuilderFactoryMock = $this->createMock(PaymentContextBuilderFactoryInterface::class);

        $this->shippingOriginProvider = $this->createMock(ShippingOriginProvider::class);

        $this->factory = new CheckoutPaymentContextFactory(
            $this->checkoutLineItemsManager,
            $this->totalProcessorProvider,
            $this->paymentLineItemConverter,
            $this->shippingOriginProvider,
            $this->paymentContextBuilderFactoryMock
        );
    }

    protected function tearDown()
    {
        unset(
            $this->factory,
            $this->paymentContextBuilderFactoryMock,
            $this->paymentLineItemConverter,
            $this->contextBuilderMock,
            $this->totalProcessorProvider,
            $this->checkoutLineItemsManager
        );
    }

    public function testCreate()
    {
        $checkout = $this->prepareCheckout();

        $convertedLineItems = new DoctrinePaymentLineItemCollection([
            new PaymentLineItem([])
        ]);

        $this->paymentLineItemConverter
            ->expects($this->once())
            ->method('convertLineItems')
            ->willReturn($convertedLineItems);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setLineItems')
            ->with($convertedLineItems);

        $this->factory->create($checkout);
    }

    public function testWithNullLineItems()
    {
        $checkout = $this->prepareCheckout();

        $this->paymentLineItemConverter
            ->expects($this->once())
            ->method('convertLineItems')
            ->willReturn(null);

        $this->contextBuilderMock
            ->expects($this->never())
            ->method('setLineItems');

        $shippingOrigin = new ShippingOrigin();
        $this->shippingOriginProvider
            ->expects($this->once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        $this->factory->create($checkout);
    }

    /**
     * @return Checkout
     */
    protected function prepareCheckout()
    {
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $shippingMethod = 'SomeShippingMethod';
        $amount = 100;
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $websiteMock = $this->createMock(Website::class);
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
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->setWebsite($websiteMock);

        $this->contextBuilderMock
            ->method('setShippingAddress')
            ->with($address);

        $this->contextBuilderMock
            ->method('setBillingAddress')
            ->with($address);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setShippingMethod')
            ->with($shippingMethod);

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setSubTotal')
            ->with(Price::create($subtotal->getAmount(), $subtotal->getCurrency()))
            ->willReturnSelf();

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setCurrency')
            ->with($checkout->getCurrency());

        $this->contextBuilderMock
            ->expects($this->once())
            ->method('setWebsite')
            ->with($checkout->getWebsite());

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
            ->with($checkout, (string)$checkout->getId())
            ->willReturn($this->contextBuilderMock);

        $this->checkoutLineItemsManager
            ->expects(static::once())
            ->method('getData')
            ->willReturn($checkoutLineItems);

        $this->totalProcessorProvider
            ->expects(static::once())
            ->method('getTotal')
            ->with($checkout)
            ->willReturn($subtotal);

        return $checkout;
    }
}
