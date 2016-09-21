<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

class ReindexationTriggerEventTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialization()
    {
        $reindexationEvent = new ReindexationTriggerEvent(
            self::class,
            1024,
            [1, 2, 3],
            true
        );

        $this->assertEquals(self::class, $reindexationEvent->getClassName());
        $this->assertEquals(1024, $reindexationEvent->getWebsiteId());
        $this->assertSame([1, 2, 3], $reindexationEvent->getIds());
        $this->assertTrue($reindexationEvent->isScheduled());
    }
}
