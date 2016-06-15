<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentMethodProvider;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class OrderPaymentMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTransactionProvider| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionProvider;

    /**
     * @var OrderPaymentMethodProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrderPaymentMethodProvider($this->paymentTransactionProvider);
    }

    public function testFalseGetData()
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

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn(false);

        $method = $this->provider->getData($context);
        $this->assertEquals(false, $method);
    }

    public function testTrueGetData()
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

        $paymentMethod = 'some_method';

        /** @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject $context */
        $paymentTransaction = $this->getMock('OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction');

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn($paymentTransaction);

        $paymentTransaction->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $method = $this->provider->getData($context);
        $this->assertEquals($paymentMethod, $method);
    }
}
