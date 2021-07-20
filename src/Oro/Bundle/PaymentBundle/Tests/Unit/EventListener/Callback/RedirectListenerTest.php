<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\EventListener\Callback\RedirectListener;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class RedirectListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RedirectListener */
    protected $listener;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    protected $session;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var PaymentResultMessageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $messageProvider;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->paymentTransaction = new PaymentTransaction();
        $this->messageProvider = $this->createMock(PaymentResultMessageProviderInterface::class);

        $this->listener = new RedirectListener($this->session, $this->messageProvider);
    }

    protected function tearDown(): void
    {
        unset($this->listener, $this->paymentTransaction, $this->session, $this->paymentMethodProvider);
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

    public function testOnReturnWithoutTransaction()
    {
        $event = new CallbackReturnEvent();

        $this->listener->onReturn($event);

        $this->assertNotInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $event->getResponse());
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
     * @param Response $expectedResponse
     * @param string $expectedFlashError
     */
    public function testOnError(
        $errorAlreadyInFlashBag,
        $options,
        $expectedResponse,
        $expectedFlashError = null
    ) {
        $this->paymentTransaction->setTransactionOptions($options);

        $this->messageProvider->expects($this->once())
            ->method('getErrorMessage')
            ->willReturn($expectedFlashError);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        /** @var FlashBagInterface|\PHPUnit\Framework\MockObject\MockObject $flashBag */
        $flashBag = $this->createMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');

        $flashBag->expects($this->once())
            ->method('has')
            ->with('error')
            ->willReturn($errorAlreadyInFlashBag);

        $flashBag->expects($errorAlreadyInFlashBag ? $this->never() : $this->once())
            ->method('add')
            ->with('error', $expectedFlashError);

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
                'expectedResponse' => new RedirectResponse('testUrl'),
                'expectedFlashError' => 'oro.payment.result.error_single_method'
            ],
            [
                'errorAlreadyInFlashBag' => true,
                'options' => ['someAnotherValue'],
                'expectedResponse' => Response::create(null, Response::HTTP_FORBIDDEN),
                'expectedFlashError' => null
            ],
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl'),
                'expectedFlashError' => 'oro.payment.result.error_single_method'
            ],
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl'),
                'expectedFlashError' => 'oro.payment.result.error_single_method'
            ],
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl'),
                'expectedFlashError' => 'oro.payment.result.error_multiple_methods'
            ]
        ];
    }

    private function assertResponses(Response $expectedResponse, Response $actualResponse)
    {
        // Hack response datetime because of requests might have different datetime
        $expectedResponse->setDate($actualResponse->getDate());
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
