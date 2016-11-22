<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ReindexationRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialization()
    {
        $reindexationEvent = new ReindexationRequestEvent(
            [static::class, 'AnotherClass'],
            [1024, 1025],
            [1, 2, 3],
            true
        );

        $this->assertEquals([static::class, 'AnotherClass'], $reindexationEvent->getClassesNames());
        $this->assertEquals([1024, 1025], $reindexationEvent->getWebsitesIds());
        $this->assertSame([1, 2, 3], $reindexationEvent->getIds());
        $this->assertTrue($reindexationEvent->isScheduled());
    }
}
