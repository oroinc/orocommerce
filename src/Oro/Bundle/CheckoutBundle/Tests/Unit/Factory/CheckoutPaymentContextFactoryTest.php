<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingOriginProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Tests\Unit\Context\PaymentLineItemTrait;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutPaymentContextFactoryTest extends TestCase
{
    use PaymentLineItemTrait;

    private CheckoutLineItemsManager|MockObject $checkoutLineItemsManager;

    private SubtotalProviderInterface|MockObject $checkoutSubtotalProvider;

    private TotalProcessorProvider|MockObject $totalProcessorProvider;

    private OrderPaymentLineItemConverterInterface|MockObject $paymentLineItemConverter;

    private PaymentContextBuilderInterface|MockObject $contextBuilder;

    private PaymentContextBuilderFactoryInterface|MockObject $paymentContextBuilderFactory;

    private CheckoutShippingOriginProviderInterface|MockObject $shippingOriginProvider;

    private CheckoutPaymentContextFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->checkoutSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);
        $this->contextBuilder = $this->createMock(PaymentContextBuilderInterface::class);
        $this->paymentLineItemConverter = $this->createMock(OrderPaymentLineItemConverterInterface::class);
        $this->paymentContextBuilderFactory = $this->createMock(PaymentContextBuilderFactoryInterface::class);
        $this->shippingOriginProvider = $this->createMock(CheckoutShippingOriginProviderInterface::class);

        $this->factory = new CheckoutPaymentContextFactory(
            $this->checkoutLineItemsManager,
            $this->checkoutSubtotalProvider,
            $this->totalProcessorProvider,
            $this->paymentLineItemConverter,
            $this->shippingOriginProvider,
            $this->paymentContextBuilderFactory
        );
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(
        ?AddressInterface $address = null,
        string $currency = 'USD',
        string $shippingMethod = 'SomeShippingMethod',
        float $amount = 0.0,
        ?Customer $customer = null,
        ?CustomerUser $customerUser = null,
        array $checkoutLineItems = [],
        ?Website $website = null
    ): void {
        $checkout = $this->getCheckout(
            $address,
            $currency,
            $shippingMethod,
            $amount,
            $customer,
            $customerUser,
            $checkoutLineItems,
            $website
        );

        $convertedLineItems = new ArrayCollection([
            $this->getPaymentLineItem()
        ]);

        $shippingOrigin = new ShippingOrigin();
        $this->shippingOriginProvider->expects(self::once())
            ->method('getShippingOrigin')
            ->with(self::identicalTo($checkout))
            ->willReturn($shippingOrigin);

        $this->contextBuilder->expects(self::once())
            ->method('setShippingOrigin')
            ->with($shippingOrigin);

        $this->paymentLineItemConverter->expects(self::once())
            ->method('convertLineItems')
            ->willReturn($convertedLineItems);

        $this->contextBuilder->expects(self::once())
            ->method('setLineItems')
            ->with($convertedLineItems)
            ->willReturnSelf();

        $this->factory->create($checkout);
    }

    public function createDataProvider(): array
    {
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $shippingMethod = 'SomeShippingMethod';
        $amount = 100;
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $checkoutLineItems = [new OrderLineItem()];
        $website = $this->createMock(Website::class);

        return [
            'all values' => [
                $address,
                $currency,
                $shippingMethod,
                $amount,
                $customer,
                $customerUser,
                $checkoutLineItems,
                $website
            ],
            'without customer and customer user (anonymous)' => [
                $address,
                $currency,
                $shippingMethod,
                $amount,
                null,
                null,
                $checkoutLineItems,
                $website
            ],
            'without customer user (reassigned)' => [
                $address,
                $currency,
                $shippingMethod,
                $amount,
                $customer,
                null,
                $checkoutLineItems,
                $website
            ]
        ];
    }

    public function testWithEmptyLineItems(): void
    {
        $checkout = $this->getCheckout();

        $this->paymentLineItemConverter->expects(self::once())
            ->method('convertLineItems')
            ->willReturn(new ArrayCollection([]));

        $this->contextBuilder->expects(self::once())
            ->method('setLineItems')
            ->willReturnSelf();

        $this->factory->create($checkout);
    }

    private function getCheckout(
        ?AddressInterface $address = null,
        string $currency = 'USD',
        string $shippingMethod = 'SomeShippingMethod',
        float $amount = 0.0,
        ?Customer $customer = null,
        ?CustomerUser $customerUser = null,
        array $checkoutLineItems = [],
        ?Website $website = null
    ): Checkout {
        $checkoutLineItems = new ArrayCollection($checkoutLineItems);

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
            ->setWebsite($website);

        $this->contextBuilder->expects($address ? $this->once() : $this->never())
            ->method('setShippingAddress')
            ->with($address);
        $this->contextBuilder->expects($address ? $this->once() : $this->never())
            ->method('setBillingAddress')
            ->with($address);
        $this->contextBuilder->expects($shippingMethod ? $this->once() : $this->never())
            ->method('setShippingMethod')
            ->with($shippingMethod);
        $this->contextBuilder->expects(self::once())
            ->method('setSubTotal')
            ->with(Price::create($subtotal->getAmount(), $subtotal->getCurrency()))
            ->willReturnSelf();
        $this->contextBuilder->expects(self::once())
            ->method('setCurrency')
            ->with($checkout->getCurrency());
        $this->contextBuilder->expects($website ? $this->once() : $this->never())
            ->method('setWebsite')
            ->with($website);
        $this->contextBuilder->expects($customer ? $this->once() : $this->never())
            ->method('setCustomer')
            ->with($customer);
        $this->contextBuilder->expects($customerUser ? $this->once() : $this->never())
            ->method('setCustomerUser')
            ->with($customerUser);
        $this->contextBuilder->expects(self::once())
            ->method('setTotal')
            ->with($subtotal->getAmount())
            ->willReturnSelf();
        $this->contextBuilder->expects(self::once())
            ->method('getResult');

        $this->paymentContextBuilderFactory->expects(self::once())
            ->method('createPaymentContextBuilder')
            ->with($checkout, (string)$checkout->getId())
            ->willReturn($this->contextBuilder);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn($checkoutLineItems);

        $this->checkoutSubtotalProvider->expects(self::once())
            ->method('getSubtotal')
            ->with($checkout)
            ->willReturn($subtotal);

        $this->totalProcessorProvider->expects(self::once())
            ->method('getTotal')
            ->with($checkout)
            ->willReturn($subtotal);

        return $checkout;
    }
}
