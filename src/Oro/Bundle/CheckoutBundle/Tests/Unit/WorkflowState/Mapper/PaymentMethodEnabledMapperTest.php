<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\PaymentMethodEnabledMapper;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\PaymentBundle\Provider\PaymentContextProvider;

class PaymentMethodEnabledMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodRegistry;

    /** @var PaymentContextProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentContextProvider;

    protected function setUp()
    {
        $this->paymentMethodRegistry = $this->getMockBuilder(PaymentMethodRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextProvider = $this->getMockBuilder(PaymentContextProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->paymentMethodRegistry, $this->paymentContextProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('payment_method_enabled', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->assertEquals('', $this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStatesEqualWithEmptyPaymentMethod()
    {
        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    public function testIsStatesEqualIncorrectPM()
    {
        $paymentMethodName = 'payment_method';
        $this->checkout->setPaymentMethod($paymentMethodName);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with($paymentMethodName)
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentContextProvider->expects($this->never())
            ->method('processContext');

        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    public function testIsStatesEqualDisabledPM()
    {
        $paymentMethod = $this->getMock(PaymentMethodInterface::class);
        $paymentMethodName = 'payment_method';

        $this->checkout->setPaymentMethod($paymentMethodName);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with($paymentMethodName)
            ->willReturn($paymentMethod);

        $paymentContext = ['processContext'];
        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->with(['entity' => $this->checkout], $this->checkout)
            ->willReturn($paymentContext);

        $paymentMethod->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);

        $paymentMethod->expects($this->never())
            ->method('isApplicable');

        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    public function testIsStatesEqualNotApplicablePM()
    {
        $paymentMethod = $this->getMock(PaymentMethodInterface::class);
        $paymentMethodName = 'payment_method';

        $this->checkout->setPaymentMethod($paymentMethodName);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with($paymentMethodName)
            ->willReturn($paymentMethod);

        $paymentContext = ['processContext'];
        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->with(['entity' => $this->checkout], $this->checkout)
            ->willReturn($paymentContext);

        $paymentMethod->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $paymentMethod->expects($this->once())
            ->method('isApplicable')
            ->with($paymentContext)
            ->willReturn(false);

        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    public function testIsStatesEqual()
    {
        $paymentMethod = $this->getMock(PaymentMethodInterface::class);
        $paymentMethodName = 'payment_method';

        $this->checkout->setPaymentMethod($paymentMethodName);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with($paymentMethodName)
            ->willReturn($paymentMethod);

        $paymentContext = ['processContext'];
        $this->paymentContextProvider->expects($this->once())
            ->method('processContext')
            ->with(['entity' => $this->checkout], $this->checkout)
            ->willReturn($paymentContext);

        $paymentMethod->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $paymentMethod->expects($this->once())
            ->method('isApplicable')
            ->with($paymentContext)
            ->willReturn(true);

        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new PaymentMethodEnabledMapper($this->paymentMethodRegistry, $this->paymentContextProvider);
    }
}
