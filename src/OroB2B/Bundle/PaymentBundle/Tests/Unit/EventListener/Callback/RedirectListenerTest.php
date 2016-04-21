<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
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
     */
    public function testOnReturn($options)
    {
        $this->paymentTransaction
            ->setTransactionOptions($options)
            ->setActive(true)
            ->setSuccessful(false);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->listener->onReturn($event);

        $this->assertFalse($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());

        /** @var RedirectResponse $response */
        $response = $event->getResponse();

        if (array_key_exists(RedirectListener::SUCCESS_URL_KEY, $options)) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
            $this->assertEquals($options[RedirectListener::SUCCESS_URL_KEY], $response->getTargetUrl());
        } else {
            $this->assertNotInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        }
    }

    /**
     * @return array
     */
    public function onReturnProvider()
    {
        return [
            [
                'options' => [RedirectListener::SUCCESS_URL_KEY => 'testUrl']
            ],
            [
                'options' => ['someAnotherValue']
            ],
        ];
    }

    /**
     * @dataProvider onErrorProvider
     * @param bool $errorAlreadyInFlashBag
     * @param array $options
     */
    public function testOnError($errorAlreadyInFlashBag, $options)
    {
        $this->paymentTransaction
            ->setTransactionOptions($options)
            ->setActive(true)
            ->setSuccessful(true);

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

        $this->assertFalse($this->paymentTransaction->isActive());
        $this->assertFalse($this->paymentTransaction->isSuccessful());

        /** @var RedirectResponse $response */
        $response = $event->getResponse();

        if (array_key_exists(RedirectListener::ERROR_URL_KEY, $options)) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
            $this->assertEquals($options[RedirectListener::ERROR_URL_KEY], $response->getTargetUrl());
        } else {
            $this->assertNotInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        }
    }

    /**
     * @return array
     */
    public function onErrorProvider()
    {
        return [
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::ERROR_URL_KEY => 'testUrl']
            ],
            [
                'errorAlreadyInFlashBag' => true,
                'options' => ['someAnotherValue']
            ],
        ];
    }
}
