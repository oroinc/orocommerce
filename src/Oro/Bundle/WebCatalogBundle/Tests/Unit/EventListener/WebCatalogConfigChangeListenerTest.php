<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebCatalogConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var WebCatalogConfigChangeListener */
    private $webCatalogConfigChangeListener;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->webCatalogConfigChangeListener = new WebCatalogConfigChangeListener($this->dispatcher);
    }

    public function testOnConfigurationUpdate()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects($this->any())
            ->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(true);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([], [], [], true, ['main']), ReindexationRequestEvent::EVENT_NAME);

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }

    public function testOnOtherConfigurationUpdate()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects($this->any())
            ->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(false);

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }
}
