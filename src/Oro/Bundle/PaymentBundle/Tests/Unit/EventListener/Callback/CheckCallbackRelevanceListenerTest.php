<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\EventListener\Callback\CheckCallbackRelevanceListener;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckCallbackRelevanceListenerTest extends TestCase
{
    private PaymentMethodProviderInterface&MockObject $paymentMethodProvider;
    private PaymentStatusManager&MockObject $paymentStatusManager;
    private DoctrineHelper&MockObject $doctrineHelper;
    private CheckCallbackRelevanceListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new CheckCallbackRelevanceListener(
            $this->paymentMethodProvider,
            $this->paymentStatusManager,
            $this->doctrineHelper
        );
    }

    public function testOnErrorWithoutTransaction(): void
    {
        $event = new CallbackErrorEvent();

        $this->paymentMethodProvider
            ->expects(self::never())
            ->method('hasPaymentMethod');

        $this->listener->onError($event);
        self::assertFalse($event->isPropagationStopped());
    }

    public function testOnErrorWithNotApplicablePaymentMethod(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getEntity');

        $this->listener->onError($event);
        self::assertFalse($event->isPropagationStopped());
    }

    public function testOnErrorWithoutExistingOrder(): void
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
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn(null);

        $this->paymentStatusManager
            ->expects(self::never())
            ->method('getPaymentStatus');

        $this->listener->onError($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://example.com/failure-url', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnErrorWithPendingOrder(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackErrorEvent();
        $originalResponse = $event->getResponse();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PENDING));

        $this->listener->onError($event);

        self::assertSame($originalResponse, $event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testOnErrorWithPaidOrder(): void
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
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PAID_IN_FULL));

        $this->listener->onError($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://example.com/failure-url', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnErrorWithPaidOrderWithoutFailureUrl(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PAID_IN_FULL));

        $this->listener->onError($event);

        /** @var Response $response */
        $response = $event->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnReturnWithoutTransaction(): void
    {
        $event = new CallbackReturnEvent();

        $this->paymentMethodProvider
            ->expects(self::never())
            ->method('hasPaymentMethod');

        $this->listener->onReturn($event);
        self::assertFalse($event->isPropagationStopped());
    }

    public function testOnReturnWithNotApplicablePaymentMethod(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(false);

        $this->doctrineHelper
            ->expects(self::never())
            ->method('getEntity');

        $this->listener->onReturn($event);
        self::assertFalse($event->isPropagationStopped());
    }

    public function testOnReturnWithoutExistingOrder(): void
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
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn(null);

        $this->paymentStatusManager
            ->expects(self::never())
            ->method('getPaymentStatus');

        $this->listener->onReturn($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://example.com/failure-url', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnReturnWithPendingOrder(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackReturnEvent();
        $originalResponse = $event->getResponse();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PENDING));

        $this->listener->onReturn($event);

        self::assertSame($originalResponse, $event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testOnReturnWithPaidOrder(): void
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
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PAID_IN_FULL));

        $this->listener->onReturn($event);

        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://example.com/failure-url', $response->getTargetUrl());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testOnReturnWithPaidOrderWithoutFailureUrl(): void
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod('payment_method');
        $paymentTransaction->setEntityClass(\stdClass::class);
        $paymentTransaction->setEntityIdentifier(5);

        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider
            ->expects(self::once())
            ->method('hasPaymentMethod')
            ->with('payment_method')
            ->willReturn(true);

        $order = new Order();
        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntity')
            ->with(\stdClass::class, 5)
            ->willReturn($order);

        $this->paymentStatusManager
            ->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PAID_IN_FULL));

        $this->listener->onReturn($event);

        $response = $event->getResponse();
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertTrue($event->isPropagationStopped());
    }
}
