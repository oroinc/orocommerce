<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\EventListener\Callback\RedirectListener;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;

class RedirectListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var RedirectListener */
    protected $listener;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var PaymentMethodProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodProvider;

    /** @var PaymentTransaction */
    protected $paymentTransaction;



    protected function setUp()
    {
        $this->session = $this->createMock(Session::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProvider::class);
        $this->paymentTransaction = new PaymentTransaction();
        $this->listener = new RedirectListener($this->session, $this->paymentMethodProvider);
    }

    protected function tearDown()
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
     * @param array|null $applicablePaymentMethods
     * @param string $expectedFlashError
     */
    public function testOnError(
        $errorAlreadyInFlashBag,
        $options,
        $expectedResponse,
        $applicablePaymentMethods,
        $expectedFlashError = null
    ) {
        $this->paymentTransaction->setTransactionOptions($options);

        $this->paymentMethodProvider->expects($this->once())
            ->method('getApplicablePaymentMethodsForTransaction')
            ->willReturn($applicablePaymentMethods);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($this->paymentTransaction);

        /** @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject $flashBag */
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
        $firstPaymentMethod = $this->createMock(PaymentMethodInterface::class);
        $secondPaymentMethod = $this->createMock(PaymentMethodInterface::class);
        return [
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl'),
                'applicablePaymentMethods' => null,
                'expectedFlashError' => 'oro.payment.result.error_single_method'
            ],
            [
                'errorAlreadyInFlashBag' => true,
                'options' => ['someAnotherValue'],
                'expectedResponse' => Response::create(null, Response::HTTP_FORBIDDEN),
                'applicablePaymentMethods' => null,
                'expectedFlashError' => null
            ],
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl'),
                'applicablePaymentMethods' => [],
                'expectedFlashError' => 'oro.payment.result.error_single_method'
            ],
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl'),
                'applicablePaymentMethods' => [$firstPaymentMethod],
                'expectedFlashError' => 'oro.payment.result.error_single_method'
            ],
            [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILURE_URL_KEY => 'testUrl'],
                'expectedResponse' => new RedirectResponse('testUrl'),
                'applicablePaymentMethods' => [$firstPaymentMethod, $secondPaymentMethod],
                'expectedFlashError' => 'oro.payment.result.error_multiple_methods'
            ]
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
