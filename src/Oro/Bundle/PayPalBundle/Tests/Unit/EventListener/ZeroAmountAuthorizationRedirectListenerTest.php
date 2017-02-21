<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener;

use Oro\Bundle\PayPalBundle\EventListener\ZeroAmountAuthorizationRedirectListener;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;

class ZeroAmountAuthorizationRedirectListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayPalCreditCardConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var ZeroAmountAuthorizationRedirectListener */
    private $listener;

    protected function setUp()
    {
        $this->config = $this->createMock(PayPalCreditCardConfigProviderInterface::class);
        $this->listener = new ZeroAmountAuthorizationRedirectListener($this->config);
    }

    protected function tearDown()
    {
        unset($this->config, $this->listener);
    }

    public function testOnRequirePaymentRedirectEnabled()
    {
        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('getIdentifier')
            ->willReturn('payment_method_id');

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $methodConfig = $this->createMock(PayPalCreditCardConfigInterface::class);
        $methodConfig->expects(static::once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(true);

        $this->config->expects(static::once())
            ->method('getPaymentConfig')
            ->with('payment_method_id')
            ->willReturn($methodConfig);

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertFalse($event->isRedirectRequired());
    }

    public function onRequirePaymentRedirectDisabled()
    {
        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $paymentMethod */
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(static::once())
            ->method('getIdentifier')
            ->willReturn('payment_method_id');

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $methodConfig = $this->createMock(PayPalCreditCardConfigInterface::class);
        $methodConfig->expects(static::once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(false);

        $this->config->expects(static::once())
            ->method('getPaymentConfig')
            ->with('payment_method_id')
            ->willReturn($methodConfig);

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertTrue($event->isRedirectRequired());
    }
}
