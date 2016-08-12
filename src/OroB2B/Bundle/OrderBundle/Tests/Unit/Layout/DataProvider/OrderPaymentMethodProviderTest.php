<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentMethodProvider;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
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

    public function testFalseGetPaymentMethod()
    {
        $order = new Order();

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn(false);

        $method = $this->provider->getPaymentMethod($order);
        $this->assertEquals(false, $method);
    }

    public function testTrueGetPaymentMethod()
    {
        $order = new Order();

        $paymentMethod = 'some_method';

        /** @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject $paymentTransaction */
        $paymentTransaction = $this->getMock('OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction');

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn($paymentTransaction);

        $paymentTransaction->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $method = $this->provider->getPaymentMethod($order);
        $this->assertEquals($paymentMethod, $method);
    }
}
