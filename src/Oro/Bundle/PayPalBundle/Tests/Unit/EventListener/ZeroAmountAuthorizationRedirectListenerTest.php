<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener;

use Oro\Bundle\PayPalBundle\EventListener\ZeroAmountAuthorizationRedirectListener;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class ZeroAmountAuthorizationRedirectListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowGatewayConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var ZeroAmountAuthorizationRedirectListener */
    private $listener;

    protected function setUp()
    {
        $this->config = $this->getMock(PayflowGatewayConfigInterface::class);
        $this->listener = new ZeroAmountAuthorizationRedirectListener($this->config);
    }

    protected function tearDown()
    {
        unset($this->config, $this->listener);
    }

    public function testOnRequirePaymentRedirectEnabled()
    {
        $paymentMethod = $this->getMock(PaymentMethodInterface::class);
        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->config->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(true);

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertFalse($event->isRedirectRequired());
    }

    public function onRequirePaymentRedirectDisabled()
    {
        $paymentMethod = $this->getMock(PaymentMethodInterface::class);
        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->config->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(false);

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertTrue($event->isRedirectRequired());
    }
}
