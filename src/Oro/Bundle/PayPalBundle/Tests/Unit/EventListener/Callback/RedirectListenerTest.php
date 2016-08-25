<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response as PayflowResponse;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;

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
     * @dataProvider onErrorProvider
     * @param bool $errorAlreadyInFlashBag
     * @param array $options
     * @param array $receivedResponse
     * @param Response $expectedResponse
     * @param string $expectedFlashError
     */
    public function testOnError(
        $errorAlreadyInFlashBag,
        $options,
        $receivedResponse,
        $expectedResponse,
        $expectedFlashError = null
    ) {
        $this->paymentTransaction
            ->setTransactionOptions($options)
            ->setResponse($receivedResponse);

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
                'options' => [RedirectListener::FAILED_SHIPPING_ADDRESS_URL_KEY => 'failedShippingUrl'],
                'receivedResponse' => [PayflowResponse::RESULT_KEY => ResponseStatusMap::FIELD_FORMAT_ERROR],
                'expectedResponse' => new RedirectResponse('failedShippingUrl'),
                'expectedFlashError' => 'oro.paypal.result.incorrect_shipping_address_error'
            ],
        ];
    }

    public function testOnErrorWithoutTransaction()
    {
        $event = new CallbackErrorEvent();

        $this->listener->onError($event);

        $this->assertNotInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $event->getResponse());
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
