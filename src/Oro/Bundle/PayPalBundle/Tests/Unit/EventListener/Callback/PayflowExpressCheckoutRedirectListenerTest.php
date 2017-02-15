<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowExpressCheckoutRedirectListener;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;

class PayflowExpressCheckoutRedirectListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowExpressCheckoutRedirectListener */
    protected $listener;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var PaymentMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodProvider;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransaction = new PaymentTransaction();
        $this->listener = new PayflowExpressCheckoutRedirectListener($this->session, $this->paymentMethodProvider);
    }

    public function testOnReturnWithoutErrorInFlashBag()
    {
        $this->paymentTransaction
            ->setSuccessful(false)
            ->setTransactionOptions(['failureUrl' => 'failUrlForExpressCheckout'])
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->createMock(FlashBagInterface::class);

        $flashBag->expects($this->once())
            ->method('has')
            ->with('error')
            ->willReturn(false);

        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'oro.payment.result.error');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onReturn($event);

        $this->assertResponses(new RedirectResponse('failUrlForExpressCheckout'), $event->getResponse());
    }

    public function testOnErrorWithWrongTransaction()
    {
        $this->paymentTransaction
            ->setSuccessful(false)
            ->setTransactionOptions(['failureUrl' => 'failUrlForExpressCheckout'])
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->listener->onReturn($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testOnReturnWithErrorInFlashBag()
    {
        $this->paymentTransaction
            ->setSuccessful(false)
            ->setTransactionOptions(['failureUrl' => 'failUrlForExpressCheckout'])
            ->setPaymentMethod('payment_method');

        $this->paymentMethodProvider
            ->expects(static::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->createMock(FlashBagInterface::class);

        $flashBag->expects($this->once())
            ->method('has')
            ->with('error')
            ->willReturn(true);

        $flashBag->expects($this->never())
            ->method('add')
            ->with('error', 'oro.payment.result.error');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onReturn($event);

        $this->assertResponses(new RedirectResponse('failUrlForExpressCheckout'), $event->getResponse());
    }

    public function testOnErrorWithoutTransaction()
    {
        $event = new CallbackErrorEvent();

        $this->listener->onReturn($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    /**
     * @param Response $expectedResponse
     * @param Response $actualResponse
     */
    private function assertResponses(Response $expectedResponse, Response $actualResponse)
    {
        // Hack response datetime because of requests might have different datetime
        $expectedResponse->setDate($actualResponse->getDate());
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
