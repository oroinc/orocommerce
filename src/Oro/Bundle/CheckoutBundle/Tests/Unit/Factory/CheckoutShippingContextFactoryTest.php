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
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingLineItemTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutShippingContextFactoryTest extends TestCase
{
    use ShippingLineItemTrait;

    private CheckoutLineItemsManager&MockObject $checkoutLineItemsManager;

    private SubtotalProviderInterface&MockObject $checkoutSubtotalProvider;

    private OrderShippingLineItemConverterInterface&MockObject $shippingLineItemConverter;

    private ShippingContextBuilderInterface&MockObject $contextBuilder;

    private CheckoutShippingOriginProviderInterface&MockObject $shippingOriginProvider;

    private ShippingContextBuilderFactoryInterface&MockObject $shippingContextBuilderFactory;

    private CustomerUserRelationsProvider&MockObject $customerUserRelationsProvider;

    private CheckoutShippingContextFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->checkoutSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->contextBuilder = $this->createMock(ShippingContextBuilderInterface::class);
        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);
        $this->shippingOriginProvider = $this->createMock(CheckoutShippingOriginProviderInterface::class);
        $this->shippingContextBuilderFactory = $this->createMock(ShippingContextBuilderFactoryInterface::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);

        $this->factory = new CheckoutShippingContextFactory(
            $this->checkoutLineItemsManager,
            $this->checkoutSubtotalProvider,
            $this->shippingLineItemConverter,
            $this->shippingOriginProvider,
            $this->shippingContextBuilderFactory
        );
        $this->factory->setCustomerUserRelationsProvider($this->customerUserRelationsProvider);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(?Customer $customer, ?CustomerUser $customerUser): void
    {
        $checkout = $this->prepareCheckout($customer, $customerUser);
        $convertedLineItems = new ArrayCollection([
            $this->getShippingLineItem()
        ]);

        $this->shippingLineItemConverter->expects(self::once())
            ->method('convertLineItems')
            ->willReturn($convertedLineItems);

        $this->contextBuilder->expects(self::once())
            ->method('setLineItems')
            ->with($convertedLineItems);

        $this->factory->create($checkout);
    }

    public function createDataProvider(): array
    {
        return [
            'with customer' => [new Customer(), new CustomerUser()],
            'anonymous (no customer, no customer user)' => [null, null],
        ];
    }

    private function prepareCheckout(?Customer $customer, ?CustomerUser $customerUser): Checkout
    {
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $paymentMethod = 'SomePaymentMethod';
        $amount = 100;
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

        $expectedCustomer = $customer;
        if (null === $customer) {
            $expectedCustomer = new Customer();
            $this->customerUserRelationsProvider->expects(self::once())
                ->method('getCustomerIncludingEmpty')
                ->with($customerUser)
                ->willReturn($expectedCustomer);
        } else {
            $this->customerUserRelationsProvider->expects(self::never())
                ->method('getCustomerIncludingEmpty');
        }

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
            ->with($expectedCustomer);

        $this->contextBuilder->expects($customerUser ? self::once() : self::never())
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
