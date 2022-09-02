<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList\Assignment;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use PHPUnit\Framework\TestCase;

class ProcessEventTest extends TestCase
{
    public function testBaseMethods()
    {
        $cpl = new CombinedPriceList();
        $associations = ['config' => true];
        $event = new ProcessEvent($cpl, $associations, true);
        $this->assertSame($cpl, $event->getCombinedPriceList());
        $this->assertSame($associations, $event->getAssociations());
        $this->assertTrue($event->isSkipUpdateNotification());
        $this->assertNull($event->getVersion());

        $event->setVersion(100);
        $this->assertEquals(100, $event->getVersion());
    }
}
