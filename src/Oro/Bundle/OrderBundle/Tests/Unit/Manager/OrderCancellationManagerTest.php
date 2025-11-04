<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Manager\OrderCancellationManager;
use Oro\Bundle\OrderBundle\Provider\OrderPaymentStatusProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderCancellationManagerTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private ConfigManager|MockObject $configManager;
    private OrderPaymentStatusProvider|MockObject $orderPaymentStatusProvider;
    private TranslatorInterface|MockObject $translator;
    private OrderCancellationManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->orderPaymentStatusProvider = $this->createMock(OrderPaymentStatusProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->manager = new OrderCancellationManager(
            $this->doctrine,
            $this->configManager,
            $this->orderPaymentStatusProvider,
            $this->translator,
            [OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN],
            ['not_shipped'],
            [PaymentStatuses::PENDING, PaymentStatuses::DECLINED, PaymentStatuses::CANCELED]
        );
    }

    public function testSetInternalStatusesAllowingCancellation(): void
    {
        $statuses = [
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            OrderStatusesProviderInterface::INTERNAL_STATUS_PENDING
        ];

        $this->manager->setInternalStatusesAllowingCancellation($statuses);

        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_PENDING,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        self::assertTrue($this->manager->canBeCanceled($order));
    }

    public function testSetShippingStatusesAllowingCancellation(): void
    {
        $statuses = ['not_shipped', 'partially_shipped'];

        $this->manager->setShippingStatusesAllowingCancellation($statuses);

        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'partially_shipped',
            PaymentStatuses::PENDING
        );

        self::assertTrue($this->manager->canBeCanceled($order));
    }

    public function testSetPaymentStatusesAllowingCancellation(): void
    {
        $statuses = [PaymentStatuses::PENDING, PaymentStatuses::AUTHORIZED];

        $this->manager->setPaymentStatusesAllowingCancellation($statuses);

        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'not_shipped',
            PaymentStatuses::AUTHORIZED
        );

        self::assertTrue($this->manager->canBeCanceled($order));
    }

    public function testCanBeCanceledReturnsTrueWhenAllConditionsMet(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        self::assertTrue($this->manager->canBeCanceled($order));
    }

    public function testCanBeCanceledReturnsFalseWhenInternalStatusNotAllowed(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        self::assertFalse($this->manager->canBeCanceled($order));
    }

    public function testCanBeCanceledReturnsFalseWhenShippingStatusNotAllowed(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'shipped',
            PaymentStatuses::PENDING
        );

        self::assertFalse($this->manager->canBeCanceled($order));
    }

    public function testCanBeCanceledReturnsFalseWhenPaymentStatusNotAllowed(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'not_shipped',
            PaymentStatuses::PAID_IN_FULL
        );

        self::assertFalse($this->manager->canBeCanceled($order));
    }

    public function testGetCannotBeCanceledReasonsReturnsEmptyArrayWhenCanBeCanceled(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        $reasons = $this->manager->getCannotBeCanceledReasons($order);

        self::assertEmpty($reasons);
    }

    public function testGetCannotBeCanceledReasonsReturnsInternalStatusReason(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        $translatedMessage = 'Internal status "Closed" does not allow cancellation';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.order.cannot_be_canceled_reason.not_allowed_internal_status',
                [
                    '%status%' => 'Closed',
                    '%allowedStatuses%' => OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                ]
            )
            ->willReturn($translatedMessage);

        $reasons = $this->manager->getCannotBeCanceledReasons($order);

        self::assertCount(1, $reasons);
        self::assertContains($translatedMessage, $reasons);
    }

    public function testGetCannotBeCanceledReasonsReturnsShippingStatusReason(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'shipped',
            PaymentStatuses::PENDING
        );

        $translatedMessage = 'Shipping status "Shipped" does not allow cancellation';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.order.cannot_be_canceled_reason.not_allowed_shipping_status',
                [
                    '%status%' => 'Shipped',
                    '%allowedStatuses%' => 'not_shipped',
                ]
            )
            ->willReturn($translatedMessage);

        $reasons = $this->manager->getCannotBeCanceledReasons($order);

        self::assertCount(1, $reasons);
        self::assertContains($translatedMessage, $reasons);
    }

    public function testGetCannotBeCanceledReasonsReturnsPaymentStatusReason(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'not_shipped',
            PaymentStatuses::PAID_IN_FULL
        );

        $translatedMessage = 'Payment status "paid_in_full" does not allow cancellation';
        $this->translator->expects(self::once())
            ->method('trans')
            ->with(
                'oro.order.cannot_be_canceled_reason.not_allowed_payment_status',
                [
                    '%status%' => PaymentStatuses::PAID_IN_FULL,
                    '%allowedStatuses%' => implode(', ', [
                        PaymentStatuses::PENDING,
                        PaymentStatuses::DECLINED,
                        PaymentStatuses::CANCELED
                    ]),
                ]
            )
            ->willReturn($translatedMessage);

        $reasons = $this->manager->getCannotBeCanceledReasons($order);

        self::assertCount(1, $reasons);
        self::assertContains($translatedMessage, $reasons);
    }

    public function testGetCannotBeCanceledReasonsReturnsMultipleReasons(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            'shipped',
            PaymentStatuses::PAID_IN_FULL
        );

        $this->translator->expects(self::exactly(3))
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return 'Translated: ' . $key;
            });

        $reasons = $this->manager->getCannotBeCanceledReasons($order);

        self::assertCount(3, $reasons);
    }

    public function testCancelSetsInternalStatusToCancelled(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        $cancelledStatus = $this->createEnumOption(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
            'Cancelled'
        );

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(ExtendHelper::buildEnumOptionId(
                Order::INTERNAL_STATUS_CODE,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
            ))
            ->willReturn($cancelledStatus);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($repository);

        $this->manager->cancel($order);

        self::assertSame($cancelledStatus, $order->getInternalStatus());
    }

    public function testCancelThrowsExceptionWhenOrderCannotBeCanceled(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturn('Cannot cancel order');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot cancel order');

        $this->manager->cancel($order);
    }

    public function testGetInternalOrderStatusCanceled(): void
    {
        $cancelledStatus = $this->createEnumOption(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
            'Cancelled'
        );

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(ExtendHelper::buildEnumOptionId(
                Order::INTERNAL_STATUS_CODE,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
            ))
            ->willReturn($cancelledStatus);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(EnumOption::class)
            ->willReturn($repository);

        $result = $this->manager->getInternalOrderStatusCanceled();

        self::assertSame($cancelledStatus, $result);
    }

    public function testIsCanceledReturnsTrueWhenOrderIsCancelled(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        self::assertTrue($this->manager->isCanceled($order));
    }

    public function testIsCanceledReturnsFalseWhenOrderIsNotCancelled(): void
    {
        $order = $this->createOrderWithStatuses(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            'not_shipped',
            PaymentStatuses::PENDING
        );

        self::assertFalse($this->manager->isCanceled($order));
    }

    private function createOrderWithStatuses(
        string $internalStatusId,
        string $shippingStatusId,
        string $paymentStatus
    ): Order {
        $order = $this->getMockBuilder(Order::class)
            ->addMethods(['getInternalStatus', 'setInternalStatus', 'getShippingStatus'])
            ->getMock();

        $internalStatus = $this->createEnumOption($internalStatusId, \ucfirst($internalStatusId));
        $shippingStatus = $this->createEnumOption($shippingStatusId, \ucfirst($shippingStatusId));


        // Preserve current internal status to allow getInternalStatus() return the value set by setInternalStatus()
        $currentInternalStatus = $internalStatus;

        $order->expects(self::any())
            ->method('getInternalStatus')
            ->willReturnCallback(function () use (&$currentInternalStatus) {
                return $currentInternalStatus;
            });

        $order->expects(self::any())
            ->method('setInternalStatus')
            ->willReturnCallback(function ($status) use (&$currentInternalStatus, $order) {
                $currentInternalStatus = $status;
                return $order;
            });

        $order->expects(self::any())->method('getShippingStatus')->willReturn($shippingStatus);

        $this->orderPaymentStatusProvider->expects(self::any())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn($paymentStatus);

        return $order;
    }

    private function createEnumOption(string $internalId, string $name): EnumOption
    {
        $enumOption = $this->createMock(EnumOption::class);
        $enumOption->expects(self::any())->method('getInternalId')->willReturn($internalId);
        $enumOption->expects(self::any())->method('getName')->willReturn($name);

        return $enumOption;
    }
}
