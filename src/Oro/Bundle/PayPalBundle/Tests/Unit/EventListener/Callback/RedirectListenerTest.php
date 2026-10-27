<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response as PayflowResponse;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

class RedirectListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTransactionProvider;

    /** @var RedirectListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->listener = new RedirectListener(
            $this->requestStack,
            $this->paymentMethodProvider,
            $this->paymentTransactionProvider
        );
    }

    public function testOnErrorWithoutPaymentTransaction()
    {
        $event = new CallbackErrorEvent();

        $this->paymentMethodProvider->expects($this->never())
            ->method('hasPaymentMethod');

        $this->listener->onError($event);
    }

    public function testOnErrorWithoutPaymentMethod()
    {
        $event = new CallbackErrorEvent();

        $paymentTransaction = new PaymentTransaction();

        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->willReturn(false);

        $this->session->expects($this->never())
            ->method('getFlashBag');

        $this->listener->onError($event);
    }

    /**
     * @dataProvider onErrorProvider
     */
    public function testOnError(
        bool $errorAlreadyInFlashBag,
        array $options,
        array $receivedResponse,
        Response $expectedResponse,
        ?string $expectedFlashError,
        bool $expectedTransactionFailed
    ) {
        $paymentTransaction = (new PaymentTransaction())
            ->setActive(true)
            ->setSuccessful(false)
            ->setTransactionOptions($options)
            ->setResponse($receivedResponse);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $flashBag = new FlashBag();
        if ($errorAlreadyInFlashBag) {
            $flashBag->add('error', 'test msg');
        }

        $this->session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->willReturn(true);

        // The listener stops the propagation, so it has to persist the failed transaction itself.
        $this->paymentTransactionProvider->expects($expectedTransactionFailed ? $this->once() : $this->never())
            ->method('savePaymentTransaction')
            ->with($this->identicalTo($paymentTransaction));

        $this->listener->onError($event);

        $this->assertResponses($expectedResponse, $event->getResponse());
        $this->assertSame($expectedTransactionFailed, $event->isPropagationStopped());
        $this->assertSame(!$expectedTransactionFailed, $paymentTransaction->isActive());
        $this->assertFalse($paymentTransaction->isSuccessful());

        if ($errorAlreadyInFlashBag) {
            $this->assertContains('test msg', $flashBag->get('error', []));
        }
        if ($expectedFlashError) {
            $this->assertContains($expectedFlashError, $flashBag->get('error', []));
        }
    }

    public function onErrorProvider(): array
    {
        $message = 'Field format error: 10736-A match of the Shipping Address City, State, and Postal Code failed.';

        return [
            'field format error without the shipping address message is not handled' => [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILED_SHIPPING_ADDRESS_URL_KEY => 'failedShippingUrl'],
                'receivedResponse' => [PayflowResponse::RESULT_KEY => ResponseStatusMap::FIELD_FORMAT_ERROR],
                'expectedResponse' => new Response('', Response::HTTP_FORBIDDEN),
                'expectedFlashError' => null,
                'expectedTransactionFailed' => false,
            ],
            'shipping address error redirects and persists the failed transaction' => [
                'errorAlreadyInFlashBag' => false,
                'options' => [RedirectListener::FAILED_SHIPPING_ADDRESS_URL_KEY => 'failedShippingUrl'],
                'receivedResponse' => [
                    PayflowResponse::RESULT_KEY => ResponseStatusMap::FIELD_FORMAT_ERROR,
                    PayflowResponse::RESPMSG_KEY => $message,
                ],
                'expectedResponse' => new RedirectResponse('failedShippingUrl'),
                'expectedFlashError' => 'oro.paypal.result.incorrect_shipping_address_error',
                'expectedTransactionFailed' => true,
            ],
            'shipping address error with an error already in the flash bag' => [
                'errorAlreadyInFlashBag' => true,
                'options' => [RedirectListener::FAILED_SHIPPING_ADDRESS_URL_KEY => 'failedShippingUrl'],
                'receivedResponse' => [
                    PayflowResponse::RESULT_KEY => ResponseStatusMap::FIELD_FORMAT_ERROR,
                    PayflowResponse::RESPMSG_KEY => $message,
                ],
                'expectedResponse' => new RedirectResponse('failedShippingUrl'),
                'expectedFlashError' => null,
                'expectedTransactionFailed' => true,
            ],
        ];
    }

    public function testOnErrorWithNonFieldFormatError()
    {
        $paymentTransaction = (new PaymentTransaction())
            ->setResponse([PayflowResponse::RESULT_KEY => ResponseStatusMap::DECLINED]);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->session->expects($this->never())
            ->method('getFlashBag');

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->willReturn(true);

        $this->paymentTransactionProvider->expects($this->never())
            ->method('savePaymentTransaction');

        $this->listener->onError($event);

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnErrorWithoutTransaction()
    {
        $event = new CallbackErrorEvent();

        $this->listener->onError($event);

        $this->assertNotInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    private function assertResponses(Response $expectedResponse, Response $actualResponse): void
    {
        // Hack response datetime because of requests might have different datetime
        $expectedResponse->setDate($actualResponse->getDate());
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}
