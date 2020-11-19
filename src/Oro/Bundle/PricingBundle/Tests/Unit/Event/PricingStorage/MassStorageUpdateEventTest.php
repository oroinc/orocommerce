<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\PricingStorage;

use Oro\Bundle\PricingBundle\Event\PricingStorage\MassStorageUpdateEvent;

class MassStorageUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $ids = [1, 3];
        $event = new MassStorageUpdateEvent($ids);
        $this->assertSame($ids, $event->getPriceListIds());
    }
}
