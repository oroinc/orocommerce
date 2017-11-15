<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\ProductBundle\EventListener\ProductImageDemoDataFixturesListener;

class ProductImageDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OptionalListenerManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var MigrationDataFixturesEvent|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var ProductImageDemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);

        $this->listener = new ProductImageDemoDataFixturesListener($this->listenerManager);

        $this->event = $this->createMock(MigrationDataFixturesEvent::class);
    }

    public function testOnPreLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('disableListeners')
            ->with(ProductImageDemoDataFixturesListener::LISTENERS);

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPreLoadNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onPreLoad($this->event);
    }

    public function testOnPostLoad()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(ProductImageDemoDataFixturesListener::LISTENERS);

        $this->listener->onPostLoad($this->event);
    }

    public function testOnPostLoadNoDemoFixtures()
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onPostLoad($this->event);
    }
}
