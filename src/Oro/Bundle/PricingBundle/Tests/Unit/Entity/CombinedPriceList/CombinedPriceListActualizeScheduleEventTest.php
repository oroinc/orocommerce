<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\CombinedPriceList;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListActualizeScheduleEvent;
use PHPUnit\Framework\TestCase;

class CombinedPriceListActualizeScheduleEventTest extends TestCase
{
    public function testEvent()
    {
        $cpl = new CombinedPriceList();
        $event = new CombinedPriceListActualizeScheduleEvent($cpl);
        $this->assertSame($cpl, $event->getCombinedPriceList());
    }
}
