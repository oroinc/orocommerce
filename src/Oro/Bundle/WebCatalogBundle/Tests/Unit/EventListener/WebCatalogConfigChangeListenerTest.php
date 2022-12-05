<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebCatalogConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private WebCatalogConfigChangeListener $webCatalogConfigChangeListener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->webCatalogConfigChangeListener = new WebCatalogConfigChangeListener(
            $this->messageProducer,
            $this->dispatcher
        );
    }

    public function testOnConfigurationUpdate(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(self::any())
            ->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(true);

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([], [], [], true, ['main']), ReindexationRequestEvent::EVENT_NAME);

        $webCatalogId = 42;
        $event->expects(self::any())
            ->method('getNewValue')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn($webCatalogId);

        $this->messageProducer
            ->expects(self::once())
            ->method('send')
            ->with(
                WebCatalogCalculateCacheTopic::getName(),
                [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalogId]
            );

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }

    public function testOnOtherConfigurationUpdate(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(self::any())
            ->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(false);

        $this->dispatcher->expects(self::never())
            ->method('dispatch');

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }
}
