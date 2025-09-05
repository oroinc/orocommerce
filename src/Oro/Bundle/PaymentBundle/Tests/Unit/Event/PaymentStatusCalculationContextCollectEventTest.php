<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class PaymentStatusCalculationContextCollectEventTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testConstructorSetsEntity(): void
    {
        $entity = new Order();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        self::assertSame($entity, $event->getEntity());
    }

    public function testPropertyAccessors(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $contextData = [
            'total' => 100.0,
            'currency' => 'USD',
            'paymentTransactions' => [],
        ];

        self::assertPropertyAccessors($event, [
            ['contextData', $contextData, true],
        ]);
    }

    public function testGetContextItemReturnsNullForNonExistentKey(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        self::assertNull($event->getContextItem('nonexistent'));
    }

    public function testGetContextItemReturnsValueForExistingKey(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $event->setContextData(['total' => 150.0]);

        self::assertEquals(150.0, $event->getContextItem('total'));
    }

    public function testSetContextItemAddsNewItem(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $result = $event->setContextItem('currency', 'EUR');

        self::assertSame($event, $result);
        self::assertEquals('EUR', $event->getContextItem('currency'));
        self::assertEquals(['currency' => 'EUR'], $event->getContextData());
    }

    public function testSetContextItemOverwritesExistingItem(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $event->setContextItem('amount', 100.0);
        $event->setContextItem('amount', 200.0);

        self::assertEquals(200.0, $event->getContextItem('amount'));
        self::assertEquals(['amount' => 200.0], $event->getContextData());
    }

    public function testSetContextItemWithNullValue(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $event->setContextItem('nullable', null);

        self::assertNull($event->getContextItem('nullable'));
        self::assertEquals(['nullable' => null], $event->getContextData());
    }

    public function testSetContextItemWithComplexData(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $complexData = [
            'nested' => ['value' => 42],
            'object' => new \stdClass(),
        ];

        $event->setContextItem('complex', $complexData);

        self::assertEquals($complexData, $event->getContextItem('complex'));
    }

    public function testMultipleContextItems(): void
    {
        $entity = new \stdClass();
        $event = new PaymentStatusCalculationContextCollectEvent($entity);

        $event->setContextItem('item1', 'value1');
        $event->setContextItem('item2', 'value2');
        $event->setContextItem('item3', 'value3');

        self::assertEquals('value1', $event->getContextItem('item1'));
        self::assertEquals('value2', $event->getContextItem('item2'));
        self::assertEquals('value3', $event->getContextItem('item3'));

        $expectedData = [
            'item1' => 'value1',
            'item2' => 'value2',
            'item3' => 'value3',
        ];

        self::assertEquals($expectedData, $event->getContextData());
    }
}
