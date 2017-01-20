<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowExpressCheckoutListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class PayflowExpressCheckoutListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PayflowExpressCheckoutListener */
    protected $listener;

    /** @var PaymentMethodProvidersRegistryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
    protected $logger;

    protected function setUp()
    {
        $this->paymentMethodRegistry = $this->getMockBuilder(PaymentMethodProvidersRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock('Psr\Log\LoggerInterface');

        $this->listener = new PayflowExpressCheckoutListener($this->paymentMethodRegistry);
        $this->listener->setLogger($this->logger);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->paymentMethodRegistry, $this->logger);
    }

    public function testOnError()
    {
        $transaction = new PaymentTransaction();
        $transaction
            ->setSuccessful(true)
            ->setActive(true);

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

        $paymentMethod = $this->createMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->with('complete', $transaction);

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $paymentMethodProvider->expects(static::any())
            ->method('hasPaymentMethod')
            ->with('payment_method_id')
            ->willReturn(true);
        $paymentMethodProvider->expects(static::any())
            ->method('getPaymentMethod')
            ->with('payment_method_id')
            ->willReturn($paymentMethod);

        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());

        $this->listener->onReturn($event);

        $this->assertEquals('action', $transaction->getAction());
        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
        $this->assertArraySubset($data, $transaction->getResponse());
    }

    /**
     * @dataProvider testOnReturnWithoutTokenProvider
     * @param array $data
     * @param string $transactionReference
     */
    public function testOnReturnWithoutToken(array $data, $transactionReference)
    {
        $transaction = new PaymentTransaction();
        $transaction
            ->setReference($transactionReference);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($transaction);

        $this->paymentMethodRegistry->expects($this->never())->method($this->anything());

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());

        $this->listener->onReturn($event);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    /**
     * @return array
     */
    public function testOnReturnWithoutTokenProvider()
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

        $paymentMethod = $this->createMock('Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->willThrowException(new \InvalidArgumentException());

        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $paymentMethodProvider->expects(static::any())
            ->method('hasPaymentMethod')
            ->with('payment_method_id')
            ->willReturn(true);
        $paymentMethodProvider->expects(static::any())
            ->method('getPaymentMethod')
            ->with('payment_method_id')
            ->willReturn($paymentMethod);

        $this->paymentMethodRegistry->expects(static::once())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $event = new CallbackReturnEvent($data);
        $event->setPaymentTransaction($transaction);

        $this->logger->expects($this->once())->method('error')->with(
            $this->isType('string'),
            $this->logicalAnd(
                $this->isType('array'),
                $this->isEmpty()
            )
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
        $this->listener->onReturn($event);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }
}
