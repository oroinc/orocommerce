<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\PaymentStatus\Context;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContextFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class PaymentStatusCalculationContextFactoryTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private PaymentStatusCalculationContextFactory $factory;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->factory = new PaymentStatusCalculationContextFactory($this->eventDispatcher);
    }

    public function testCreatePaymentStatusCalculationContextWithEmptyEventData(): void
    {
        $order = new Order();

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(function (PaymentStatusCalculationContextCollectEvent $event) use ($order) {
                    return $event->getEntity() === $order && $event->getContextData() === [];
                })
            )
            ->willReturnCallback(function (PaymentStatusCalculationContextCollectEvent $event) {
                return $event;
            });

        $result = $this->factory->createPaymentStatusCalculationContext($order);

        self::assertNull($result->get('nonexistent'));
    }

    public function testCreatePaymentStatusCalculationContextWithEventData(): void
    {
        $order = new Order();

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (PaymentStatusCalculationContextCollectEvent $event) use ($order) {
                    return $event->getEntity() === $order;
                })
            )
            ->willReturnCallback(function (PaymentStatusCalculationContextCollectEvent $event) {
                $event->setContextData([
                    'total' => 100.0,
                    'paymentTransactions' => [],
                    'customData' => 'test',
                ]);

                return $event;
            });

        $result = $this->factory->createPaymentStatusCalculationContext($order);

        self::assertEquals(100.0, $result->get('total'));
        self::assertEquals([], $result->get('paymentTransactions'));
        self::assertEquals('test', $result->get('customData'));
        self::assertNull($result->get('nonexistent'));
    }

    public function testCreatePaymentStatusCalculationContextWithDifferentEntity(): void
    {
        $entity = new \stdClass();

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (PaymentStatusCalculationContextCollectEvent $event) use ($entity) {
                    return $event->getEntity() === $entity;
                })
            )
            ->willReturnCallback(function (PaymentStatusCalculationContextCollectEvent $event) {
                $event->setContextItem('entityType', get_class($event->getEntity()));

                return $event;
            });

        $result = $this->factory->createPaymentStatusCalculationContext($entity);

        self::assertEquals(\stdClass::class, $result->get('entityType'));
    }

    public function testCreatePaymentStatusCalculationContextWithMultipleEventListeners(): void
    {
        $order = new Order();

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (PaymentStatusCalculationContextCollectEvent $event) use ($order) {
                    return $event->getEntity() === $order;
                })
            )
            ->willReturnCallback(function (PaymentStatusCalculationContextCollectEvent $event) {
                // Simulate multiple event listeners adding data
                $event->setContextItem('listener1', 'data1');
                $event->setContextItem('listener2', 'data2');
                $event->setContextItem('total', 250.0);

                return $event;
            });

        $result = $this->factory->createPaymentStatusCalculationContext($order);

        self::assertEquals('data1', $result->get('listener1'));
        self::assertEquals('data2', $result->get('listener2'));
        self::assertEquals(250.0, $result->get('total'));
    }

    public function testCreatePaymentStatusCalculationContextEventIsDispatchedOnce(): void
    {
        $order = new Order();

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (PaymentStatusCalculationContextCollectEvent $event) {
                return $event;
            });

        $this->factory->createPaymentStatusCalculationContext($order);
    }

    public function testCreatePaymentStatusCalculationContextWithComplexData(): void
    {
        $order = new Order();
        $complexData = [
            'nested' => [
                'array' => ['value1', 'value2'],
                'object' => new \stdClass(),
            ],
            'number' => 42,
            'boolean' => true,
            'null' => null,
        ];

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (PaymentStatusCalculationContextCollectEvent $event) use ($complexData) {
                $event->setContextData($complexData);

                return $event;
            });

        $result = $this->factory->createPaymentStatusCalculationContext($order);

        self::assertEquals($complexData['nested'], $result->get('nested'));
        self::assertEquals(42, $result->get('number'));
        self::assertTrue($result->get('boolean'));
        self::assertNull($result->get('null'));
    }
}
