<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class RequirePaymentRedirectEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethod;

    /** @var RequirePaymentRedirectEvent */
    private $event;

    protected function setUp(): void
    {
        $this->paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->event = new RequirePaymentRedirectEvent($this->paymentMethod);
    }

    public function testIsRedirectNeeded()
    {
        $this->assertFalse($this->event->isRedirectRequired());
        $this->event->setRedirectRequired(true);
        $this->assertTrue($this->event->isRedirectRequired());
    }

    public function testGetPaymentMethod()
    {
        $this->assertSame($this->paymentMethod, $this->event->getPaymentMethod());
    }
}
