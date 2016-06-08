<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\EventListener\Callback\PayflowListener;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayflowListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowListener */
    protected $listener;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentMethodRegistry = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PayflowListener($this->session, $this->paymentMethodRegistry);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->session);
    }

    public function testOnNotify()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction('action')
            ->setPaymentMethod('payment_method')
            ->setResponse(['existing' => 'response']);

        $paymentMethod = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (PaymentTransaction $executePaymentTransaction) use ($paymentTransaction) {
                $this->assertSame($paymentTransaction, $executePaymentTransaction);
                $this->assertEquals(PayflowGateway::COMPLETE, $executePaymentTransaction->getAction());
            });

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with($paymentTransaction->getPaymentMethod())
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

    public function testOnNotifyTransactionWithReference()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setPaymentMethod('payment_method')
            ->setAction('action')
            ->setReference('reference');

        $this->paymentMethodRegistry->expects($this->never())
            ->method($this->anything());

        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);
        $event->setPaymentTransaction($paymentTransaction);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onNotify($event);
        $this->assertEquals('action', $paymentTransaction->getAction());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnNotifyWithoutTransaction()
    {
        $this->paymentMethodRegistry->expects($this->never())
            ->method($this->anything());

        $event = new CallbackNotifyEvent(['RESULT' => ResponseStatusMap::APPROVED]);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onNotify($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnNotifyPaymentMethodNotFound()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setAction('action');
        $paymentTransaction->setPaymentMethod('not_exists_payment_method');

        $this->paymentMethodRegistry->expects($this->once())
            ->method('getPaymentMethod')
            ->with($paymentTransaction->getPaymentMethod())
            ->willThrowException(new \InvalidArgumentException);

        $event = new CallbackNotifyEvent([]);
        $event->setPaymentTransaction($paymentTransaction);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());

        $this->listener->onNotify($event);

        $this->assertEquals('action', $paymentTransaction->getAction());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
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
