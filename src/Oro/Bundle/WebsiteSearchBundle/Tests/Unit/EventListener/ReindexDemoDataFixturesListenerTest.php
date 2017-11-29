<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexDemoDataFixturesListener;

class ReindexDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    const LISTENERS = [
        'test_listener_1',
        'test_listener_2',
    ];

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var OptionalListenerManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var ReindexDemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new ReindexDemoDataFixturesListener($this->listenerManager, $this->dispatcher);
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    public function testOnPreLoad()
    {
        $this->listenerManager->expects($this->once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($this->createMock(MigrationDataFixturesEvent::class));
    }

    public function testOnPostLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(self::LISTENERS);
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
