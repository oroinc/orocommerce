<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use OroB2B\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class RequirePaymentRedirectEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $paymentMethod;

    /** @var RequirePaymentRedirectEvent */
    private $event;

    protected function setUp()
    {
        $this->paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $this->event = new RequirePaymentRedirectEvent($this->paymentMethod);
    }

    protected function tearDown()
    {
        unset($this->paymentMethod, $this->event);
    }

    public function testIsRedirectNeeded()
    {
        $this->assertFalse($this->event->isRedirectRequired());
        $this->event->setRedirectRequired(true);
        $this->assertTrue($this->event->isRedirectRequired());
    }

    public function testGetPaymentMethod()
    {
        $this->assertInstanceOf(
            'OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface',
            $this->event->getPaymentMethod()
        );
    }
}
