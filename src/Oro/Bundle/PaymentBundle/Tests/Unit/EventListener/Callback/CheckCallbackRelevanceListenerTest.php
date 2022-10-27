<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\EventListener\Callback\CheckCallbackRelevanceListener;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckCallbackRelevanceListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMethodProvider;

    /**
     * @var PaymentStatusProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentStatusProvider;
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;
    /**
     * @var CheckCallbackRelevanceListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new CheckCallbackRelevanceListener(
            $this->paymentMethodProvider,
            $this->paymentStatusProvider,
            $this->doctrineHelper
        );
    }

    public function testOnErrorWithoutTransaction()
    {
        $event = new CallbackErrorEvent();

        $this->paymentMethodProvider
            ->expects($this->never())
            ->method('hasPaymentMethod');

        $this->listener->onError($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnErrorWithNotApplicablePaymentMethod()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntity');

        $this->listener->onError($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnErrorWithoutExistingOrder()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);
        $paymentTransaction->setTransactionOptions([
            'failureUrl' => 'https://example.com/failure-url',
        ]);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn(null);

        $this->paymentStatusProvider
            ->expects($this->never())
            ->method('getPaymentStatus');

        $this->listener->onError($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://example.com/failure-url', $response->getTargetUrl());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testOnErrorWithPandingOrder()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackErrorEvent();
        $originalResponse = $event->getResponse();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::PENDING);

        $this->listener->onError($event);

        $this->assertSame($originalResponse, $event->getResponse());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnErrorWithPaidOrder()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);
        $paymentTransaction->setTransactionOptions([
            'failureUrl' => 'https://example.com/failure-url',
        ]);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::FULL);

        $this->listener->onError($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://example.com/failure-url', $response->getTargetUrl());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testOnErrorWithPaidOrderWithoutFailureUrl()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::FULL);

        $this->listener->onError($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testOnReturnWithoutTransaction()
    {
        $event = new CallbackReturnEvent();

        $this->paymentMethodProvider
            ->expects($this->never())
            ->method('hasPaymentMethod');

        $this->listener->onReturn($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnReturnWithNotApplicablePaymentMethod()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntity');

        $this->listener->onReturn($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnReturnWithoutExistingOrder()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);
        $paymentTransaction->setTransactionOptions([
            'failureUrl' => 'https://example.com/failure-url',
        ]);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn(null);

        $this->paymentStatusProvider
            ->expects($this->never())
            ->method('getPaymentStatus');

        $this->listener->onReturn($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://example.com/failure-url', $response->getTargetUrl());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testOnReturnWithPandingOrder()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackReturnEvent();
        $originalResponse = $event->getResponse();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::PENDING);

        $this->listener->onReturn($event);

        $this->assertSame($originalResponse, $event->getResponse());
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnReturnWithPaidOrder()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);
        $paymentTransaction->setTransactionOptions([
            'failureUrl' => 'https://example.com/failure-url',
        ]);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::FULL);

        $this->listener->onReturn($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://example.com/failure-url', $response->getTargetUrl());
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testOnReturnWithPaidOrderWithoutFailureUrl()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusProvider
            ->expects($this->once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn(PaymentStatusProvider::FULL);

        $this->listener->onReturn($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertTrue($event->isPropagationStopped());
    }
}
