<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\SystemShippingOriginProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingLineItemTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderShippingContextFactoryTest extends TestCase
{
    use ShippingLineItemTrait;

    private const TEST_PAYMENT_METHOD = 'SomePaymentMethod';
    private const TEST_SHIPPING_METHOD = 'SomeShippingMethod';

    private ManagerRegistry|MockObject $doctrine;

    private OrderShippingLineItemConverterInterface|MockObject $shippingLineItemConverter;

    private ShippingContextBuilderFactoryInterface|MockObject $shippingContextBuilderFactory;

    private OrderShippingContextFactory $factory;

    private SystemShippingOriginProvider $systemShippingOriginProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);
        $this->shippingContextBuilderFactory = $this->createMock(ShippingContextBuilderFactoryInterface::class);
        $this->systemShippingOriginProvider = $this->createMock(SystemShippingOriginProvider::class);

        $this->factory = new OrderShippingContextFactory(
            $this->doctrine,
            $this->shippingLineItemConverter,
            $this->shippingContextBuilderFactory,
            $this->systemShippingOriginProvider
        );
    }

    private function getContextBuilder(
        AddressInterface $address,
        Price $subtotal,
        string $currency,
        Website $website,
        Customer $customer,
        CustomerUser $customerUser,
        ShippingOrigin $shippingOrigin,
    ): ShippingContextBuilderInterface|MockObject {
        $contextBuilder = $this->createMock(ShippingContextBuilderInterface::class);
        $contextBuilder->expects(self::any())
            ->method('setShippingAddress')
            ->with($address)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setBillingAddress')
            ->with($address)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setCustomer')
            ->with($customer)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setCustomerUser')
            ->with($customerUser)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setSubTotal')
            ->with($subtotal)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setCurrency')
            ->with($currency)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setWebsite')
            ->with($website)
            ->willReturnSelf();
        $contextBuilder->expects(self::once())
            ->method('setShippingOrigin')
            ->with($shippingOrigin)
            ->willReturnSelf();
        $contextBuilder->expects(self::once())
            ->method('getResult');

        return $contextBuilder;
    }

    private function getOrder(): Order
    {
        $paymentTransaction = $this->createMock(PaymentTransaction::class);
        $paymentTransaction->expects(self::once())
            ->method('getPaymentMethod')
            ->willReturn(self::TEST_PAYMENT_METHOD);

        $repository = $this->createMock(PaymentTransactionRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($paymentTransaction);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($repository);

        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $amount = 100;
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $websiteMock = $this->createMock(Website::class);

        $orderLineItem1 = new OrderLineItem();
        $orderLineItem1->setQuantity(10);
        $orderLineItem1->setPrice(Price::create($amount, $currency));

        $orderLineItem2 = new OrderLineItem();
        $orderLineItem2->setQuantity(20);
        $orderLineItem2->setPrice(Price::create($amount, $currency));

        $order = new Order();
        $order->setBillingAddress($address);
        $order->setShippingAddress($address);
        $order->setShippingMethod(self::TEST_SHIPPING_METHOD);
        $order->setCurrency($currency);
        $order->setLineItems(new ArrayCollection([$orderLineItem1, $orderLineItem2]));
        $order->setSubtotal($amount);
        $order->setCurrency($currency);
        $order->setCustomer($customer);
        $order->setCustomerUser($customerUser);
        $order->setWebsite($websiteMock);

        return $order;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreate(): void
    {
        $shippingOrigin = $this->createMock(ShippingOrigin::class);
        $this->systemShippingOriginProvider->expects(self::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);
        $order = $this->getOrder();

        $shippingLineItems = [
            $this->getShippingLineItem(quantity: 10)
                ->setPrice(Price::create($order->getSubtotal(), $order->getCurrency())),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($order->getSubtotal(), $order->getCurrency())),
        ];

        $shippingLineItemCollection = new ArrayCollection($shippingLineItems);

        $this->shippingLineItemConverter->expects(self::once())
            ->method('convertLineItems')
            ->with($order->getLineItems())
            ->willReturn($shippingLineItemCollection);

        $contextBuilder = $this->getContextBuilder(
            $order->getBillingAddress(),
            Price::create($order->getSubtotal(), $order->getCurrency()),
            $order->getCurrency(),
            $order->getWebsite(),
            $order->getCustomer(),
            $order->getCustomerUser(),
            $shippingOrigin,
        );
        $contextBuilder->expects(self::once())
            ->method('setPaymentMethod')
            ->with(self::TEST_PAYMENT_METHOD);
        $contextBuilder->expects($this->once())
            ->method('setLineItems')
            ->with($shippingLineItemCollection);

        $this->shippingContextBuilderFactory->expects(self::once())
            ->method('createShippingContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($contextBuilder);

        $this->factory->create($order);
    }

    public function testUnsupportedEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->create(new \stdClass());
    }
}
