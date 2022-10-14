<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackNotifyEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowListener;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class PayflowListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var PayflowListener */
    private $listener;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new PayflowListener($this->session, $this->paymentMethodProvider);
        $this->listener->setLogger($this->logger);
    }

    public function testOnNotify()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects(self::once())
            ->method('execute')
            ->with('complete', $paymentTransaction);

        $this->paymentMethodProvider->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);
        $this->paymentMethodProvider->expects(self::once())
            ->method('getPaymentMethod')
            ->with('payment_method')
            ->willReturn($paymentMethod);

        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $event->setPaymentTransaction($paymentTransaction);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onNotify($event);
        $this->assertEquals('action', $paymentTransaction->getAction());
        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $this->assertEquals(
            ['RESULT' => ResponseStatusMap::APPROVED, 'existing' => 'response'],
            $paymentTransaction->getResponse()
        );
    }

    public function testOnNotifyExecuteFailed()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);
        $this->paymentMethodProvider->expects(self::any())
            ->method('getPaymentMethod')
            ->with('payment_method')
            ->willReturn($paymentMethod);

        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $event->setPaymentTransaction($paymentTransaction);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->logicalAnd($this->isType('array'), $this->isEmpty()));

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnNotifyWithWrongTransaction()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $this->paymentMethodProvider->expects(self::never())
            ->method('getPaymentMethod')
            ->with('payment_method');

        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onNotify($event);
    }

    public function testOnNotifyTransactionWithReference()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setPaymentMethod('payment_method')
            ->setAction('action')
            ->setReference('reference');

        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $event->setPaymentTransaction($paymentTransaction);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onNotify($event);
        $this->assertEquals('action', $paymentTransaction->getAction());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnNotifyWithoutTransaction()
    {
        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onNotify($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnError()
    {
        $event = new CallbackReturnEvent([]);

        $this->session->expects($this->never())
            ->method($this->anything());

        $this->listener->onError($event);
    }

    public function testOnErrorWithWrongTransaction()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $this->paymentMethodProvider->expects(self::never())
            ->method('getPaymentMethod')
            ->with('payment_method');

        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $event->setPaymentTransaction($paymentTransaction);

        $this->session->expects($this->never())
            ->method($this->anything());

        $this->listener->onError($event);
    }

    public function testOnErrorNotToken()
    {
        $event = new CallbackReturnEvent(['RESULT' => ResponseStatusMap::ATTEMPT_TO_REFERENCE_A_FAILED_TRANSACTION]);

        $this->session->expects($this->never())
            ->method($this->anything());

        $this->listener->onError($event);
    }

    public function testOnErrorTokenExpired()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $event = new CallbackReturnEvent(['RESULT' => ResponseStatusMap::SECURE_TOKEN_EXPIRED]);
        $event->setPaymentTransaction($paymentTransaction);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('set')
            ->with('warning', 'oro.paypal.result.token_expired');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onError($event);
    }
}
