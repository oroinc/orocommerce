<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\ApruveBundle\EventListener\Callback\PaymentCallbackListener;
use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackNotifyEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class PaymentCallbackListenerTest extends \PHPUnit_Framework_TestCase
{
    const EVENT_DATA = [ApruvePaymentMethod::PARAM_ORDER_ID => 'sampleApuveOrderId'];

    /** @var PaymentCallbackListener */
    protected $listener;

    /** @var PaymentMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodProvider;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new PaymentCallbackListener($this->paymentMethodProvider);
        $this->listener->setLogger($this->logger);
    }

    public function testOnReturn()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method');

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->with('payment_method')
            ->willReturn($paymentMethod);

        $paymentMethod
            ->expects(static::once())
            ->method('execute')
            ->with('authorize', $paymentTransaction);

        $event = new CallbackNotifyEvent(self::EVENT_DATA);
        $event->setPaymentTransaction($paymentTransaction);

        static::assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());

        $this->listener->onReturn($event);

        static::assertEquals('action', $paymentTransaction->getAction());
        static::assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        static::assertEquals(self::EVENT_DATA, $paymentTransaction->getResponse());
    }

    public function testOnReturnWithoutTransaction()
    {
        $event = new CallbackNotifyEvent(self::EVENT_DATA);

        static::assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onReturn($event);
        static::assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnReturnWithInvalidPaymentMethod()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $this->paymentMethodProvider
            ->expects(static::never())
            ->method('getPaymentMethod')
            ->with('payment_method');

        $event = new CallbackNotifyEvent(self::EVENT_DATA);
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onReturn($event);
    }

    public function testOnReturnWithoutOrderId()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('getPaymentMethod')
            ->with('payment_method')
            ->willReturn($paymentMethod);

        $paymentMethod
            ->expects(static::never())
            ->method('execute');

        $event = new CallbackNotifyEvent([]);
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onReturn($event);
    }

    public function testOnReturnExecuteFailed()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);

        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $this->paymentMethodProvider
            ->expects(static::any())
            ->method('getPaymentMethod')
            ->with('payment_method')
            ->willReturn($paymentMethod);

        $paymentMethod
            ->expects(static::once())
            ->method('execute')
            ->willThrowException(new \InvalidArgumentException());

        $event = new CallbackNotifyEvent(self::EVENT_DATA);
        $event->setPaymentTransaction($paymentTransaction);

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with(
                static::isType('string'),
                static::logicalAnd(
                    static::isType('array'),
                    static::isEmpty()
                )
            );

        $this->listener->onReturn($event);

        static::assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }
}
