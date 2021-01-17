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
    /** @var CallbackHandler */
    protected $handler;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new CallbackHandler($this->eventDispatcher, $this->paymentTransactionProvider);
    }

    public function testHandleNoEntity()
    {
        $event = new CallbackReturnEvent();

        $result = $this->handler->handle($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testHandle()
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

    public function testHandleEventFailedOnFirstDispatchNotSaved()
    {
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setPaymentMethod('paymentMethod');
        $event->setPaymentTransaction($transaction);

        $this->paymentTransactionProvider->expects($this->never())->method('savePaymentTransaction');

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with($event, CallbackReturnEvent::NAME)
            ->willReturnCallback(
                function (CallbackReturnEvent $event, $name) {
                    $event->markFailed();
                }
            );

        $result = $this->handler->handle($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }
}
