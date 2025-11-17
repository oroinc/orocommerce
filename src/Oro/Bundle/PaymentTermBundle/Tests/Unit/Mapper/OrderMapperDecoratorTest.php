<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Mapper\OrderMapperDecorator;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderMapperDecoratorTest extends TestCase
{
    private MapperInterface|MockObject $orderMapper;
    private PaymentTermAssociationProvider|MockObject $paymentTermAssociationProvider;
    private OrderMapperDecorator $decorator;

    protected function setUp(): void
    {
        $this->orderMapper = $this->createMock(MapperInterface::class);
        $this->paymentTermAssociationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $this->decorator = new OrderMapperDecorator(
            $this->orderMapper,
            $this->paymentTermAssociationProvider,
            'payment_term'
        );
    }

    public function testMapWithPaymentTerm(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_term');

        $order = new Order();
        $paymentTerm = new PaymentTerm();
        $data = ['paymentTerm' => $paymentTerm];

        $this->orderMapper->expects(self::once())
            ->method('map')
            ->with($checkout, $data, [])
            ->willReturn($order);

        $this->paymentTermAssociationProvider->expects(self::once())
            ->method('setPaymentTerm')
            ->with($order, $paymentTerm);

        $result = $this->decorator->map($checkout, $data);

        self::assertSame($order, $result);
    }

    public function testMapWithoutPaymentTerm(): void
    {
        $checkout = new Checkout();
        $order = new Order();
        $data = [];

        $this->orderMapper->expects(self::once())
            ->method('map')
            ->with($checkout, $data, [])
            ->willReturn($order);

        $this->paymentTermAssociationProvider->expects(self::never())
            ->method('setPaymentTerm');

        $result = $this->decorator->map($checkout, $data);

        self::assertSame($order, $result);
    }

    public function testMapWithInvalidPaymentTermType(): void
    {
        $checkout = new Checkout();
        $order = new Order();
        // Invalid payment term type (string instead of PaymentTerm object)
        $data = ['paymentTerm' => 'invalid'];

        $this->orderMapper->expects(self::once())
            ->method('map')
            ->with($checkout, $data, [])
            ->willReturn($order);

        // Payment term should not be assigned when payment term is not an instance of PaymentTerm
        $this->paymentTermAssociationProvider->expects(self::never())
            ->method('setPaymentTerm');

        $result = $this->decorator->map($checkout, $data);

        self::assertSame($order, $result);
    }
}
