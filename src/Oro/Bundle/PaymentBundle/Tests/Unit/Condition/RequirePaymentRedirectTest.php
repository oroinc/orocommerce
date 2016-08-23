<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\PaymentBundle\Condition\RequirePaymentRedirect;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class RequirePaymentRedirectTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_METHOD_KEY = 'payment_method';

    /** @var RequirePaymentRedirect */
    protected $condition;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    public function setUp()
    {
        $this->paymentMethodRegistry = $this->getMock(PaymentMethodRegistry::class);
        $this->dispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->condition = new RequirePaymentRedirect($this->paymentMethodRegistry, $this->dispatcher);
    }

    public function testGetName()
    {
        $this->assertEquals('require_payment_redirect', $this->condition->getName());
    }

    public function testInitialize()
    {
        $this->assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_term'])
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
        $paymentMethod = $this->getMock(PaymentMethodInterface::class);

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with('payment_term')
            ->willReturn($paymentMethod);

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                ['orob2b_payment.require_payment_redirect', $event],
                ['orob2b_payment.require_payment_redirect.payment_term', $event]
            );

        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_term']);
        $this->assertFalse($this->condition->evaluate($context, $errors));
    }

    public function testToArray()
    {
        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_term']);
        $result = $this->condition->toArray();
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('@' . RequirePaymentRedirect::NAME, $result);
        $resultSection = $result['@' . RequirePaymentRedirect::NAME];
        $this->assertInternalType('array', $resultSection);
        $this->assertArrayHasKey('parameters', $resultSection);
        $this->assertContains('payment_term', $resultSection['parameters']);
    }

    public function testCompile()
    {
        $this->condition->initialize([self::PAYMENT_METHOD_KEY => 'payment_term']);
        $result = $this->condition->compile('');
        $this->assertContains(RequirePaymentRedirect::NAME, $result);
        $this->assertContains('payment_term', $result);
    }
}
