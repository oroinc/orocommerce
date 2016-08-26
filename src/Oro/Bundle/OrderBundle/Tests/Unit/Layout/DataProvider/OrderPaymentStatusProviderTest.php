<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

class OrderPaymentStatusProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentStatusProvider| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentProvider;

    /**
     * @var OrderPaymentStatusProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->paymentProvider = $this->getMockBuilder('Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider')
            ->disableOriginalConstructor()
            ->getMock();

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
