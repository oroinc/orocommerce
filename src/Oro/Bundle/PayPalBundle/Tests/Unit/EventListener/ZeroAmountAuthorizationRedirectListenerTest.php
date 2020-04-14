<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\EventListener\ZeroAmountAuthorizationRedirectListener;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;

class ZeroAmountAuthorizationRedirectListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PayPalCreditCardConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var ZeroAmountAuthorizationRedirectListener */
    private $listener;

    protected function setUp(): void
    {
        $this->config = $this->createMock(PayPalCreditCardConfigProviderInterface::class);
        $this->listener = new ZeroAmountAuthorizationRedirectListener($this->config);
    }

    public function testOnRequirePaymentRedirectEnabled()
    {
        $paymentMethod = $this->mockPaymentMethod();

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $methodConfig = $this->createMock(PayPalCreditCardConfigInterface::class);
        $methodConfig->expects(static::once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(true);

        $this->config->expects(static::once())
            ->method('hasPaymentConfig')
            ->with('payment_method_id')
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
        $paymentMethod = $this->mockPaymentMethod();

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $methodConfig = $this->createMock(PayPalCreditCardConfigInterface::class);
        $methodConfig->expects(static::once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn(false);

        $this->config->expects(static::once())
            ->method('hasPaymentConfig')
            ->with('payment_method_id')
            ->willReturn(true);

        $this->config->expects(static::once())
            ->method('getPaymentConfig')
            ->with('payment_method_id')
            ->willReturn($methodConfig);

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertTrue($event->isRedirectRequired());
    }

    public function testOnRequirePaymentRedirectWhenNoPaymentMethod()
    {
        $paymentMethod = $this->mockPaymentMethod();

        $event = new RequirePaymentRedirectEvent($paymentMethod);

        $this->config
            ->expects(static::once())
            ->method('hasPaymentConfig')
            ->with('payment_method_id')
            ->willReturn(false);

        $this->config
            ->expects(static::never())
            ->method('getPaymentConfig');

        $this->listener->onRequirePaymentRedirect($event);

        $this->assertFalse($event->isRedirectRequired());
    }

    /**
     * @return PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockPaymentMethod()
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod
            ->expects(static::once())
            ->method('getIdentifier')
            ->willReturn('payment_method_id');

        return $paymentMethod;
    }
}
