<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebCatalogConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dispatcher;

    /**
     * @var WebCatalogConfigChangeListener
     */
    protected $webCatalogConfigChangeListener;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->webCatalogConfigChangeListener = new WebCatalogConfigChangeListener($this->dispatcher);
    }

    public function testOnConfigurationUpdate()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event **/
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(true);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent(), ReindexationRequestEvent::EVENT_NAME);

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }

    public function testOnOtherConfigurationUpdate()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event **/
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(false);

        $this->dispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }
}
