<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingOriginProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CheckoutShippingContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutSubtotalProvider;

    /** @var OrderShippingLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingLineItemConverter;

    /** @var ShippingContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextBuilder;

    /** @var CheckoutShippingOriginProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOriginProvider;

    /** @var ShippingContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingContextBuilderFactory;

    /** @var CheckoutShippingContextFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->checkoutSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->contextBuilder = $this->createMock(ShippingContextBuilderInterface::class);
        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);
        $this->shippingOriginProvider = $this->createMock(CheckoutShippingOriginProviderInterface::class);
        $this->shippingContextBuilderFactory = $this->createMock(ShippingContextBuilderFactoryInterface::class);

        $this->factory = new CheckoutShippingContextFactory(
            $this->checkoutLineItemsManager,
            $this->checkoutSubtotalProvider,
            $this->shippingLineItemConverter,
            $this->shippingOriginProvider,
            $this->shippingContextBuilderFactory
        );
    }

    public function testCreate()
    {
        $checkout = $this->prepareCheckout();
        $convertedLineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([])
        ]);

        $this->shippingLineItemConverter->expects(self::once())
            ->method('convertLineItems')
            ->willReturn($convertedLineItems);

        $this->contextBuilder->expects(self::once())
            ->method('setLineItems')
            ->with($convertedLineItems);

        $this->factory->create($checkout);
    }

    private function prepareCheckout(): Checkout
    {
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $paymentMethod = 'SomePaymentMethod';
        $amount = 100;
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $checkoutLineItems = new ArrayCollection([new OrderLineItem()]);
        $website = $this->createMock(Website::class);
        $shippingOrigin = $this->createMock(ShippingOrigin::class);

        $subtotal = (new Subtotal())
            ->setAmount($amount)
            ->setCurrency($currency);

        $checkout = (new Checkout())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setPaymentMethod($paymentMethod)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->setWebsite($website);

        $this->contextBuilder->expects(self::once())
            ->method('setShippingAddress')
            ->with($address);

        $this->contextBuilder->expects(self::once())
            ->method('setBillingAddress')
            ->with($address);

        $this->contextBuilder->expects(self::once())
            ->method('setPaymentMethod')
            ->with($paymentMethod);

        $this->contextBuilder->expects(self::once())
            ->method('setCustomer')
            ->with($customer);

        $this->contextBuilder->expects(self::once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->contextBuilder->expects(self::once())
            ->method('getResult');

        $this->contextBuilder->expects(self::once())
            ->method('setSubTotal')
            ->with(Price::create($subtotal->getAmount(), $subtotal->getCurrency()))
            ->willReturnSelf();

        $this->contextBuilder->expects(self::once())
            ->method('setCurrency')
            ->with($checkout->getCurrency());

        $this->contextBuilder->expects(self::once())
            ->method('setWebsite')
            ->with($website);

        $this->contextBuilder->expects(self::once())
            ->method('setShippingOrigin')
            ->with(self::identicalTo($shippingOrigin));

        $this->shippingContextBuilderFactory->expects(self::once())
            ->method('createShippingContextBuilder')
            ->with($checkout, (string)$checkout->getId())
            ->willReturn($this->contextBuilder);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn($checkoutLineItems);

        $this->checkoutSubtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->with($checkout)
            ->willReturn($subtotal);

        $this->shippingOriginProvider->expects(self::once())
            ->method('getShippingOrigin')
            ->with(self::identicalTo($checkout))
            ->willReturn($shippingOrigin);

        return $checkout;
    }
}
