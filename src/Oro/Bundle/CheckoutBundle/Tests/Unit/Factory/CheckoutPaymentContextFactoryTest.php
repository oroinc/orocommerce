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
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CheckoutPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var SubtotalProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutSubtotalProvider;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProcessorProvider;

    /** @var OrderPaymentLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentLineItemConverter;

    /** @var PaymentContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextBuilder;

    /** @var PaymentContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentContextBuilderFactory;

    /** @var CheckoutShippingOriginProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOriginProvider;

    /** @var CheckoutPaymentContextFactory */
    private $factory;

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
    ) {
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

        $convertedLineItems = new DoctrinePaymentLineItemCollection([
            new PaymentLineItem([])
        ]);

        $shippingOrigin = new ShippingOrigin();
        $this->shippingOriginProvider->expects($this->once())
            ->method('getShippingOrigin')
            ->with(self::identicalTo($checkout))
            ->willReturn($shippingOrigin);

        $this->contextBuilder->expects($this->once())
            ->method('setShippingOrigin')
            ->with($shippingOrigin);

        $this->paymentLineItemConverter->expects($this->once())
            ->method('convertLineItems')
            ->willReturn($convertedLineItems);

        $this->contextBuilder->expects($this->once())
            ->method('setLineItems')
            ->with($convertedLineItems);

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

    public function testWithEmptyLineItems()
    {
        $checkout = $this->getCheckout();

        $this->paymentLineItemConverter->expects($this->once())
            ->method('convertLineItems')
            ->willReturn(new DoctrinePaymentLineItemCollection([]));

        $this->contextBuilder->expects($this->never())
            ->method('setLineItems');

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
        $this->contextBuilder->expects($this->once())
            ->method('setSubTotal')
            ->with(Price::create($subtotal->getAmount(), $subtotal->getCurrency()))
            ->willReturnSelf();
        $this->contextBuilder->expects($this->once())
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
        $this->contextBuilder->expects($this->once())
            ->method('setTotal')
            ->with($subtotal->getAmount())
            ->willReturnSelf();
        $this->contextBuilder->expects($this->once())
            ->method('getResult');

        $this->paymentContextBuilderFactory->expects($this->once())
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
