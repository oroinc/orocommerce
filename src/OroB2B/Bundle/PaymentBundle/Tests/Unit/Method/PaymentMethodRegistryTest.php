<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentMethodRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodRegistry */
    protected $registry;

    /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $method;

    protected function setUp()
    {
        $this->registry = new PaymentMethodRegistry();

        $this->method = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->registry, $this->method);
    }

    public function testGetMethods()
    {
        $paymentMethods = $this->registry->getPaymentMethods();
        $this->assertInternalType('array', $paymentMethods);
        $this->assertEmpty($paymentMethods);
    }

    public function testAddPaymentMethod()
    {
        $this->registry->addPaymentMethod($this->method);
        $this->assertContains($this->method, $this->registry->getPaymentMethods());
    }

    public function testRegistry()
    {
        $this->method->expects($this->any())
            ->method('getType')
            ->willReturn('test_type');

        $this->registry->addPaymentMethod($this->method);
        $this->assertEquals($this->method, $this->registry->getPaymentMethod('test_type'));
        $this->assertEquals(['test_type' => $this->method], $this->registry->getPaymentMethods());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment method with "wrong_type" is missing. Registered payment methods are ""
     */
    public function testRegistryException()
    {
        $this->registry->getPaymentMethod('wrong_type');
    }
}
