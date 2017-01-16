<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Condition\RequirePaymentRedirect;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RequirePaymentRedirectTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD_KEY = 'payment_method';

    /** @var RequirePaymentRedirect */
    protected $condition;

    /** @var PaymentMethodProvidersRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodProvidersRegistry;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    public function setUp()
    {
        $this->paymentMethodProvidersRegistry = $this->createMock(PaymentMethodProvidersRegistry::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->condition = new RequirePaymentRedirect($this->paymentMethodProvidersRegistry, $this->dispatcher);
    }

    public function testGetName()
    {
        $this->assertEquals('require_payment_redirect', $this->condition->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_method'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInitializeWithException()
    {
        $this->assertNotInstanceOf(AbstractCondition::class, $this->condition->initialize([]));
    }

    public function testEvaluate()
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass(Collection::class);
        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)->getMock();

        $paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->willReturn(true);

        $paymentMethodProvider->expects($this->once())
            ->method('getPaymentMethod')
            ->with('payment_method')
            ->willReturn($paymentMethod);

        $this->paymentMethodProvidersRegistry
            ->expects($this->once())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                ['oro_payment.require_payment_redirect', $event],
                ['oro_payment.require_payment_redirect.payment_method', $event]
            );

        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_method']);
        $this->assertFalse($this->condition->evaluate($context, $errors));
    }

    public function testToArray()
    {
        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_method']);
        $result = $this->condition->toArray();
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('@' . RequirePaymentRedirect::NAME, $result);
        $resultSection = $result['@' . RequirePaymentRedirect::NAME];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains('payment_method', $resultSection['parameters']);
    }

    public function testCompile()
    {
        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_method']);
        $result = $this->condition->compile('');
        $this->assertContains(RequirePaymentRedirect::NAME, $result);
        $this->assertContains('payment_method', $result);
    }
}
