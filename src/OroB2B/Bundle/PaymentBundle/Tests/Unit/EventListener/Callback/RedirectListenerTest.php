<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\EventListener\Callback\RedirectListener;

class RedirectListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var RedirectListener */
    protected $listener;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransaction = new PaymentTransaction();
        $this->listener = new RedirectListener($this->session);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->paymentTransaction, $this->session);
    }

    /**
     * @dataProvider onReturnProvider
     * @param array $options
     * @param RedirectResponse|response $expectedResponse
     */
    public function testOnReturn($options, $expectedResponse)
    {
        $this->paymentTransaction
            ->setTransactionOptions($options);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->listener->onReturn($event);

        $this->assertResponses($expectedResponse, $event->getResponse());
    }

    /**
     * @return array
     */
    public function onReturnProvider()
    {
        return [
            [
                'options' => [RedirectListener::SUCCESS_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl')
            ],
            [
                'options' => ['someAnotherValue'],
                'expectedResponse' => Response::create(null, Response::HTTP_FORBIDDEN)
            ],
        ];
    }

    /**
     * @dataProvider onErrorProvider
     * @param bool $errorAlreadyInFlashBag
     * @param array $options
     * @param RedirectResponse|Response $expectedResponse
     */
    public function testOnError($errorAlreadyInFlashBag, $options, $expectedResponse)
    {
        $this->paymentTransaction
            ->setTransactionOptions($options);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
        $flashBag = $this->getMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');

        $flashBag->expects($this->once())
            ->method('has')
            ->with('error')
            ->willReturn($errorAlreadyInFlashBag);

        $flashBag->expects($errorAlreadyInFlashBag ? $this->never() : $this->once())
            ->method('add')
            ->with('error', 'orob2b.payment.result.error');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->listener->onError($event);

        $this->assertResponses($expectedResponse, $event->getResponse());
    }

    /**
     * @return array
     */
    public function onErrorProvider()
    {
        return [
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl')
            ],
            [
                'errorAlreadyInFlashBag' => true,
                'options' => ['someAnotherValue'],
                'expectedResponse' => Response::create(null, Response::HTTP_FORBIDDEN)
            ],
        ];
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
