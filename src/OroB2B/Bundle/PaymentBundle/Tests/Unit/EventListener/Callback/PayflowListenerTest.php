<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\EventListener\Callback\PayflowListener;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayflowListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowListener */
    protected $listener;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PayflowListener($this->session);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->session);
    }

    public function testOnNotifyWithoutTransaction()
    {
        $event = new CallbackNotifyEvent([]);

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnNotifyTransactionWithReferenceAlreadyProcessed()
    {
        $event = new CallbackNotifyEvent([]);
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setReference('PNREF');
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnNotify()
    {
        $event = new CallbackNotifyEvent(['PNREF' => 'ref']);
        $paymentTransaction = new PaymentTransaction();
        $this->assertEmpty($paymentTransaction->getReference());
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $this->assertEquals('ref', $paymentTransaction->getReference());
    }

    public function testOnNotifySuccessfulFromResponse()
    {
        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $paymentTransaction = new PaymentTransaction();
        $this->assertFalse($paymentTransaction->isSuccessful());
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $this->assertTrue($paymentTransaction->isSuccessful());
    }

    public function testOnNotifyActiveFromResponse()
    {
        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $paymentTransaction = new PaymentTransaction();
        $this->assertFalse($paymentTransaction->isActive());
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $this->assertTrue($paymentTransaction->isActive());
    }

    public function testOnNotifyAppendResponseData()
    {
        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['existing' => 'response']);
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $this->assertEquals(
            ['existing' => 'response', 'RESULT' => ResponseStatusMap::APPROVED],
            $paymentTransaction->getResponse()
        );
    }

    public function testOnNotifyWithCharge()
    {
        $event = new CallbackNotifyEvent(['PNREF' => 'ref']);
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(PaymentMethodInterface::CHARGE);

        $this->assertEmpty($paymentTransaction->getReference());
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onNotify($event);

        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $this->assertEquals('ref', $paymentTransaction->getReference());
        $this->assertFalse($paymentTransaction->isActive());
    }

    public function testOnError()
    {
        $event = new CallbackReturnEvent([]);

        $this->session->expects($this->never())->method($this->anything());

        $this->listener->onError($event);
    }

    public function testOnErrorNotToken()
    {
        $event = new CallbackReturnEvent(['RESULT' => ResponseStatusMap::ATTEMPT_TO_REFERENCE_A_FAILED_TRANSACTION]);

        $this->session->expects($this->never())->method($this->anything());

        $this->listener->onError($event);
    }

    public function testOnErrorTokenExpired()
    {
        $event = new CallbackReturnEvent(['RESULT' => ResponseStatusMap::SECURE_TOKEN_EXPIRED]);

        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
        $flashBag->expects($this->once())
            ->method('set')
            ->with('warning', 'orob2b.payment.result.token_expired');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onError($event);
    }
}
