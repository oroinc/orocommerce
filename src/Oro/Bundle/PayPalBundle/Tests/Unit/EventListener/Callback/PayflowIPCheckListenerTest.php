<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackNotifyEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListener;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PayflowIPCheckListenerTest extends \PHPUnit\Framework\TestCase
{
    private PaymentMethodProviderInterface|MockObject $paymentMethodProvider;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
    }

    public function returnConfiguredAllowedIPs(): array
    {
        return [
            'PayPal\'s IP address 1 should be allowed' => ['255.255.255.1'],
            'PayPal\'s IP address 2 should be allowed' => ['255.255.255.2'],
            'PayPal\'s IP address 3 should be allowed' => ['173.0.81.1'],
        ];
    }

    public function returnAllowedIPs(): array
    {
        return [
            'PayPal\'s IP address 1 should be allowed' => ['173.0.81.1'],
            'PayPal\'s IP address 2 should be allowed' => ['173.0.81.33'],
            'PayPal\'s IP address 3 should be allowed' => ['173.0.81.65'],
            'PayPal\'s IP address 4 should be allowed' => ['66.211.170.66'],
        ];
    }

    public function returnNotAllowedIPs(): array
    {
        return [
            'Google\'s IP address 5 should not be allowed' => ['216.58.214.206'],
            'Facebook\'s IP address 6 should not be allowed' => ['173.252.120.68'],
        ];
    }

    /**
     * @dataProvider returnAllowedIPs
     */
    public function testOnNotifyAllowed(string $remoteAddress): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $masterRequest = $this->createMock(Request::class);
        $masterRequest
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn($remoteAddress);

        /** @var RequestStack|MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($masterRequest);

        /** @var CallbackNotifyEvent|MockObject $event */
        $event = $this->createMock(CallbackNotifyEvent::class);
        $event
            ->expects($this->never())
            ->method('markFailed');
        $event
            ->expects($this->once())
            ->method('getPaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $listener = new PayflowIPCheckListener($requestStack, $this->paymentMethodProvider, []);
        $listener->onNotify($event);
    }

    /**
     * @dataProvider returnConfiguredAllowedIPs
     */
    public function testOnNotifyAllowedWithConfiguredIPs(string $remoteAddress): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $masterRequest = $this->createMock(Request::class);
        $masterRequest
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn($remoteAddress);

        /** @var RequestStack|MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($masterRequest);

        /** @var CallbackNotifyEvent|MockObject $event */
        $event = $this->createMock(CallbackNotifyEvent::class);
        $event
            ->expects($this->never())
            ->method('markFailed');
        $event
            ->expects($this->once())
            ->method('getPaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $listener = new PayflowIPCheckListener(
            $requestStack,
            $this->paymentMethodProvider,
            ['255.255.255.0/24', '173.0.81.1']
        );
        $listener->onNotify($event);
    }

    /**
     * @dataProvider returnNotAllowedIPs
     */
    public function testOnNotifyNotAllowed(string $remoteAddress): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $masterRequest = $this->createMock(Request::class);
        $masterRequest
            ->expects($this->once())
            ->method('getClientIp')
            ->willReturn($remoteAddress);

        /** @var RequestStack|MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($masterRequest);

        /** @var CallbackNotifyEvent|MockObject $event */
        $event = $this->createMock(CallbackNotifyEvent::class);
        $event
            ->expects($this->once())
            ->method('markFailed');
        $event
            ->expects($this->once())
            ->method('getPaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $listener = new PayflowIPCheckListener($requestStack, $this->paymentMethodProvider, []);
        $listener->onNotify($event);
    }

    public function testOnNotifyDontAllowIfMasterRequestEmpty(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $masterRequest = null;

        /** @var RequestStack|MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getMainRequest')
            ->willReturn($masterRequest);

        /** @var CallbackNotifyEvent|MockObject $event */
        $event = $this->createMock(CallbackNotifyEvent::class);
        $event
            ->expects($this->once())
            ->method('markFailed');
        $event
            ->expects($this->once())
            ->method('getPaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $listener = new PayflowIPCheckListener($requestStack, $this->paymentMethodProvider, []);
        $listener->onNotify($event);
    }
}
