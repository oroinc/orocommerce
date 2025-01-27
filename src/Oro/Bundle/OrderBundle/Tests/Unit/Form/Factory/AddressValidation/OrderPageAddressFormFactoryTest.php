<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Factory\AddressValidation;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Factory\AddressValidation\OrderPageAddressFormFactory;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class OrderPageAddressFormFactoryTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;

    private OrderRequestHandler&MockObject $orderRequestHandler;

    private OrderPageAddressFormFactory $addressFormFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->orderRequestHandler = $this->createMock(OrderRequestHandler::class);

        $this->addressFormFactory = new OrderPageAddressFormFactory(
            $this->formFactory,
            $this->orderRequestHandler,
            new PropertyAccessor(),
            'shippingAddress'
        );
    }

    public function testCreateAddressForm(): void
    {
        $request = new Request();
        $orderForm = $this->createMock(FormInterface::class);
        $addressForm = $this->createMock(FormInterface::class);

        $customer = new Customer();
        $this->orderRequestHandler
            ->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $customerUser = new CustomerUser();
        $this->orderRequestHandler
            ->expects(self::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $orderForm
            ->expects(self::once())
            ->method('get')
            ->with('shippingAddress')
            ->willReturn($addressForm);

        $this->formFactory
            ->expects(self::once())
            ->method('create')
            ->with(
                OrderType::class,
                self::callback(static function (Order $order) use ($customer, $customerUser) {
                    self::assertSame($customer, $order->getCustomer());
                    self::assertSame($customerUser, $order->getCustomerUser());

                    return true;
                })
            )
            ->willReturn($orderForm);

        $result = $this->addressFormFactory->createAddressForm($request);

        self::assertSame($addressForm, $result);
    }

    public function testCreateAddressFormWithExplicitAddress(): void
    {
        $request = new Request();
        $address = new OrderAddress();
        $orderForm = $this->createMock(FormInterface::class);
        $addressForm = $this->createMock(FormInterface::class);

        $customer = new Customer();
        $this->orderRequestHandler
            ->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $customerUser = new CustomerUser();
        $this->orderRequestHandler
            ->expects(self::once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $orderForm
            ->expects(self::once())
            ->method('get')
            ->with('shippingAddress')
            ->willReturn($addressForm);

        $this->formFactory
            ->expects(self::once())
            ->method('create')
            ->with(
                OrderType::class,
                self::callback(static function (Order $order) use ($customer, $customerUser, $address) {
                    self::assertSame($customer, $order->getCustomer());
                    self::assertSame($customerUser, $order->getCustomerUser());
                    self::assertSame($address, $order->getShippingAddress());

                    return true;
                })
            )
            ->willReturn($orderForm);

        $result = $this->addressFormFactory->createAddressForm($request, $address);

        self::assertSame($addressForm, $result);
    }
}
