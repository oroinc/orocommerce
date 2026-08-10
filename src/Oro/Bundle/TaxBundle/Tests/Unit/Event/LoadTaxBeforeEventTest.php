<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\LoadTaxBeforeEvent;
use PHPUnit\Framework\TestCase;

final class LoadTaxBeforeEventTest extends TestCase
{
    public function testGetObjectReturnsObjectPassedToConstructor(): void
    {
        $order = new Order();

        $event = new LoadTaxBeforeEvent($order);

        self::assertSame($order, $event->getObject());
    }
}
