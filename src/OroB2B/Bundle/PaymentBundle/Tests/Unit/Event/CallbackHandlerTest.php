<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackHandler;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class CallbackHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CallbackHandler */
    protected $handler;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
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

        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->withConsecutive(
                [CallbackReturnEvent::NAME, $event],
                [CallbackReturnEvent::NAME . '.paymentMethod', $event]
            );

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
            ->with(CallbackReturnEvent::NAME, $event)
            ->willReturnCallback(
                function ($name, CallbackReturnEvent $event) {
                    $event->markFailed();
                }
            );

        $result = $this->handler->handle($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testHandleEventFailedOnSecondDispatchNotSaved()
    {
        $event = new CallbackReturnEvent();
        $transaction = new PaymentTransaction();
        $transaction->setPaymentMethod('paymentMethod');
        $event->setPaymentTransaction($transaction);

        $this->paymentTransactionProvider->expects($this->never())->method('savePaymentTransaction');

        $this->eventDispatcher->expects($this->at(0))->method('dispatch')
            ->with(CallbackReturnEvent::NAME, $event)
            ->willReturnCallback(
                function ($name, CallbackReturnEvent $event) {
                    $this->assertFalse($event->isPropagationStopped());
                }
            );

        $this->eventDispatcher->expects($this->at(1))->method('dispatch')
            ->with(CallbackReturnEvent::NAME . '.paymentMethod', $event)
            ->willReturnCallback(
                function ($name, CallbackReturnEvent $event) {
                    $event->markFailed();
                }
            );

        $result = $this->handler->handle($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }
}
