<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

class OrderPaymentStatusProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentStatusProvider| \PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentProvider;

    /**
     * @var OrderPaymentStatusProvider
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->paymentProvider = $this->createMock(PaymentStatusProvider::class);

        $this->provider = new OrderPaymentStatusProvider($this->paymentProvider);
    }

    public function testGetPaymentStatus()
    {
        $order = new Order();

        $this->paymentProvider->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn('status');

        $status = $this->provider->getPaymentStatus($order);
        $this->assertEquals('status', $status);
    }
}
