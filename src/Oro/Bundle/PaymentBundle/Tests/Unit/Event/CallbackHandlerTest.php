<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackHandler;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class CallbackHandlerTest extends \PHPUnit\Framework\TestCase
{
    private CallbackHandler $handler;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject $paymentTransactionProvider;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->handler = new CallbackHandler($this->eventDispatcher, $this->paymentTransactionProvider);
    }

    public function testHandleNoEntity(): void
    {
        $event = new CallbackReturnEvent();

        $result = $this->handler->handle($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testHandle(): void
    {
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setPaymentMethod('paymentMethod');
        $event->setPaymentTransaction($transaction);

        $this->paymentTransactionProvider->expects($this->once())->method('savePaymentTransaction')
            ->with($transaction);

        $result = $this->handler->handle($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testHandleEventFailedOnFirstDispatchNotSaved(): void
    {
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setPaymentMethod('paymentMethod');
        $event->setPaymentTransaction($transaction);

        $this->paymentTransactionProvider->expects($this->never())->method('savePaymentTransaction');

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with($event, CallbackReturnEvent::NAME)
            ->willReturnCallback(
                function (CallbackReturnEvent $event) {
                    $event->markFailed();

                    return $event;
                }
            );

        $result = $this->handler->handle($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }
}
