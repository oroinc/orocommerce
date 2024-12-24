<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use PHPUnit\Framework\TestCase;

final class ReindexationRequestEventTest extends TestCase
{
    public function testInitialization(): void
    {
        $reindexationEvent = new ReindexationRequestEvent(
            [static::class, 'AnotherClass'],
            [1024, 1025],
            [1, 2, 3],
            false,
            ['main'],
            1000
        );

        self::assertEquals([static::class, 'AnotherClass'], $reindexationEvent->getClassesNames());
        self::assertEquals([1024, 1025], $reindexationEvent->getWebsitesIds());
        self::assertSame([1, 2, 3], $reindexationEvent->getIds());
        self::assertFalse($reindexationEvent->isScheduled());
        self::assertSame(['main'], $reindexationEvent->getFieldGroups());
        self::assertSame(1000, $reindexationEvent->getBatchSize());
    }

    public function testInitializationWithoutParameters(): void
    {
        $reindexationEvent = new ReindexationRequestEvent();

        self::assertEquals([], $reindexationEvent->getClassesNames());
        self::assertEquals([], $reindexationEvent->getWebsitesIds());
        self::assertSame([], $reindexationEvent->getIds());
        self::assertTrue($reindexationEvent->isScheduled());
        self::assertNull($reindexationEvent->getFieldGroups());
        self::assertNull($reindexationEvent->getBatchSize());
    }
}
