<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener;

use Oro\Bundle\PayPalBundle\EventListener\PayflowRequirePaymentRedirectListener;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;

use OroB2B\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;

class PayflowRequirePaymentRedirectListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowGatewayConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var PayflowRequirePaymentRedirectListener */
    private $listener;

    protected function setUp()
    {
        $this->config = $this->getMock('Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface');
        $this->listener = new PayflowRequirePaymentRedirectListener($this->config);
    }

    protected function tearDown()
    {
        unset($this->config, $this->listener);
    }

    public function testOnRequirePaymentRedirectEnabled()
    {
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->config->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(true);

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertFalse($event->isRedirectNeeded());
    }

    public function onRequirePaymentRedirectDisabled()
    {
        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->config->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(false);

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertTrue($event->isRedirectNeeded());
    }
}
