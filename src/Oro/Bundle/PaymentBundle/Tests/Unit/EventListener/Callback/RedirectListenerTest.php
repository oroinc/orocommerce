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
    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var PaymentTransaction */
    private $paymentTransaction;

    /** @var PaymentResultMessageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProvider;

    /** @var RedirectListener */
    private $listener;

    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->paymentTransaction = new PaymentTransaction();
        $this->messageProvider = $this->createMock(PaymentResultMessageProviderInterface::class);

        $this->listener = new RedirectListener($this->session, $this->messageProvider);
    }

    /**
     * @dataProvider onReturnProvider
     */
    public function testOnReturn(array $options, Response $expectedResponse): void
    {
        $this->paymentTransaction
            ->setTransactionOptions($options);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $this->listener->onReturn($event);

        $this->assertResponses($expectedResponse, $event->getResponse());
    }

    public function testOnReturnWithoutTransaction(): void
    {
        $event = new CallbackReturnEvent();

        $this->listener->onReturn($event);

        self::assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function onReturnProvider(): array
    {
        return [
            [
                'options' => [RedirectListener::SUCCESS_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl')
            ],
            [
                'options' => ['someAnotherValue'],
                'expectedResponse' => new Response(null, Response::HTTP_FORBIDDEN)
            ],
        ];
    }

    /**
     * @dataProvider onErrorProvider
     */
    public function testOnError(
        bool $errorAlreadyInFlashBag,
        array $options,
        Response $expectedResponse,
        ?string $expectedFlashError = null
    ): void {
        $this->paymentTransaction->setTransactionOptions($options);

        $this->messageProvider->expects($this->once())
            ->method('getErrorMessage')
            ->willReturn($expectedFlashError);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        $flashBag = $this->createMock(FlashBagInterface::class);

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

    public function onErrorProvider(): array
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
                'expectedResponse' => new Response(null, Response::HTTP_FORBIDDEN),
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

    private function assertResponses(Response $expectedResponse, Response $actualResponse): void
    {
        // Hack response datetime because of requests might have different datetime
        $expectedResponse->setDate($actualResponse->getDate());
        self::assertEquals($expectedResponse, $actualResponse);
    }
}
