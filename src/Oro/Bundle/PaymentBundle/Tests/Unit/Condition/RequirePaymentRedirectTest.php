<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Condition\RequirePaymentRedirect;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RequirePaymentRedirectTest extends \PHPUnit\Framework\TestCase
{
    const PAYMENT_METHOD_KEY = 'payment_method';

    /** @var RequirePaymentRedirect */
    protected $condition;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodProvider;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->condition = new RequirePaymentRedirect($this->paymentMethodProvider, $this->dispatcher);
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

    public function testInitializeWithException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertNotInstanceOf(AbstractCondition::class, $this->condition->initialize([]));
    }

    public function testEvaluate()
    {
        $context = new \stdClass();
        $errors = $this->getMockForAbstractClass(Collection::class);
        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->with('payment_method')
            ->willReturn($paymentMethod);

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, 'oro_payment.require_payment_redirect');

        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_method']);
        $this->assertFalse($this->condition->evaluate($context, $errors));
    }

    public function testToArray()
    {
        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_method']);
        $result = $this->condition->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('@' . RequirePaymentRedirect::NAME, $result);
        $resultSection = $result['@' . RequirePaymentRedirect::NAME];
        $this->assertIsArray($resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains('payment_method', $resultSection['parameters']);
    }

    public function testCompile()
    {
        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_method']);
        $result = $this->condition->compile('');
        static::assertStringContainsString(RequirePaymentRedirect::NAME, $result);
        static::assertStringContainsString('payment_method', $result);
    }
}
