<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowExpressCheckoutListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class PayflowExpressCheckoutListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var PayflowExpressCheckoutListener */
    private $listener;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new PayflowExpressCheckoutListener($this->paymentMethodProvider);
        $this->listener->setLogger($this->logger);
    }

    public function testOnError()
    {
        $this->paymentMethodProvider->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $transaction = new PaymentTransaction();
        $transaction
            ->setSuccessful(true)
            ->setActive(true)
            ->setPaymentMethod('payment_method');

        $event = new CallbackErrorEvent([]);
        $event->setPaymentTransaction($transaction);

        $this->listener->onError($event);

        $this->assertFalse($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
    }

    public function testOnErrorWithoutPaymentTransaction()
    {
        $event = new CallbackErrorEvent([]);

        $this->listener->onError($event);
    }

    public function testOnErrorWithWrongTransaction()
    {
        $this->paymentMethodProvider->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $transaction = $this->createMock(PaymentTransaction::class);
        $transaction->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn('payment_method');
        $transaction->expects($this->never())
            ->method('setSuccessful');
        $transaction->expects($this->never())
            ->method('setActive');

        $event = new CallbackErrorEvent([]);
        $event->setPaymentTransaction($transaction);

        $this->listener->onError($event);
    }

    public function testOnReturnSuccess()
    {
        $data = [
            'PayerID' => 'new payerId',
            'token' => 'token'
        ];

        $transaction = new PaymentTransaction();
        $transaction
            ->setAction('action')
            ->setPaymentMethod('payment_method_id')
            ->setReference('token')
            ->setResponse(['PayerID' => 'old payerId', 'token' => 'old token']);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($transaction);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->with('complete', $transaction);

        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with('payment_method_id')
            ->willReturn(true);
        $this->paymentMethodProvider->expects(self::any())
            ->method('getPaymentMethod')
            ->with('payment_method_id')
            ->willReturn($paymentMethod);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());

        $this->listener->onReturn($event);

        $this->assertEquals('action', $transaction->getAction());
        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $response = $transaction->getResponse();
        $this->assertSame($data['PayerID'], $response['PayerID']);
        $this->assertSame($data['token'], $response['token']);
    }

    /**
     * @dataProvider testOnReturnWithoutTokenProvider
     */
    public function testOnReturnWithoutToken(array $data, string $transactionReference)
    {
        $transaction = new PaymentTransaction();
        $transaction
            ->setReference($transactionReference);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($transaction);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());

        $this->listener->onReturn($event);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnReturnWithoutTokenProvider(): array
    {
        return [
            'without token' => [
                ['PayerID' => 'payerId'],
                'pp-token'
            ],
            'without PayerID' => [
                ['token' => 'pp-token'],
                'pp-token'
            ],
            'with incorect token' => [
                ['token' => 'wrong-token'],
                'pp-token'
            ],
        ];
    }

    public function testOnReturnWithExecuteFailed()
    {
        $data = [
            'PayerID' => 'new payerId',
            'token' => 'token'
        ];

        $transaction = new PaymentTransaction();
        $transaction
            ->setPaymentMethod('payment_method_id')
            ->setReference('token');

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->willThrowException(new \InvalidArgumentException());

        $this->paymentMethodProvider->expects(self::any())
            ->method('hasPaymentMethod')
            ->with('payment_method_id')
            ->willReturn(true);
        $this->paymentMethodProvider->expects(self::any())
            ->method('getPaymentMethod')
            ->with('payment_method_id')
            ->willReturn($paymentMethod);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($transaction);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'), $this->logicalAnd($this->isType('array'), $this->isEmpty()));

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onReturn($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }
}
