<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class WebCatalogConfigChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var WebCatalogConfigChangeListener
     */
    protected $webCatalogConfigChangeListener;

    protected function setUp()
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->webCatalogConfigChangeListener = new WebCatalogConfigChangeListener($this->dispatcher);
    }

    public function testOnConfigurationUpdate()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event **/
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $event->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(true);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(ReindexationRequestEvent::EVENT_NAME, new ReindexationRequestEvent());
        
        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }

    public function testOnOtherConfigurationUpdate()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event **/
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
