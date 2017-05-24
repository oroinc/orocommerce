<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexDemoDataFixturesListener;

class ReindexDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var ReindexDemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new ReindexDemoDataFixturesListener($this->dispatcher);
    }

    public function testOnPostLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $event->expects(self::never())
            ->method('log');
        $this->dispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->onPostLoad($event);
    }

    public function testOnPostLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $event->expects(self::once())
            ->method('log')
            ->with('running full reindexation of website index');
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                ReindexationRequestEvent::EVENT_NAME,
                self::isInstanceOf(ReindexationRequestEvent::class)
            );

        $this->listener->onPostLoad($event);
    }
}
