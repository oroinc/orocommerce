<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentStatusProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

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
        $this->paymentProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrderPaymentStatusProvider($this->paymentProvider);
    }

    public function testGetData()
    {
        $order = new Order();

        /** @var ContextDataCollection|\PHPUnit_Framework_MockObject_MockObject $data */
        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $data->expects($this->once())
            ->method('get')
            ->with('order')
            ->willReturn($order);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $context->expects($this->once())
            ->method('data')
            ->willReturn($data);

        $this->paymentProvider->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn('status');

        $status = $this->provider->getData($context);
        $this->assertEquals('status', $status);
    }
}
