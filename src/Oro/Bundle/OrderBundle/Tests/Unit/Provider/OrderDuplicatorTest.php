<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderDuplicator;
use Oro\Component\Duplicator\DuplicatorFactory;
use Oro\Component\Duplicator\DuplicatorInterface;

class OrderDuplicatorTest extends \PHPUnit\Framework\TestCase
{
    public function testDuplicate(): void
    {
        $order = new Order();
        $order1 = new Order();

        $duplicator = $this->createMock(DuplicatorInterface::class);
        $duplicator->expects(self::once())
            ->method('duplicate')
            ->with($order, self::isType('array'))
            ->willReturn($order1);

        $duplicatorFactory = $this->createMock(DuplicatorFactory::class);
        $duplicatorFactory->expects(self::once())
            ->method('create')
            ->willReturn($duplicator);

        $orderDuplicator = new OrderDuplicator($duplicatorFactory);

        $result = $orderDuplicator->duplicate($order);
        self::assertSame($order1, $result);
    }
}
