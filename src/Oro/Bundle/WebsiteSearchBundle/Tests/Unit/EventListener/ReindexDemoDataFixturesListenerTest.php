<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
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

    public function testOnPostLoad()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);
        $event->expects(self::never())
            ->method('log');

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->dispatcher->expects(self::never())
            ->method($this->anything());

        $this->listener->onPostLoad($event);
    }
}
