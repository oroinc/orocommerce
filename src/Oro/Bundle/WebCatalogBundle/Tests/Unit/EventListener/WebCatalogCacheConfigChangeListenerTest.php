<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogCacheConfigChangeListener;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class WebCatalogCacheConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private WebCatalogCacheConfigChangeListener $webCatalogConfigChangeListener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->webCatalogConfigChangeListener = new WebCatalogCacheConfigChangeListener($this->messageProducer);
    }

    public function testOnConfigurationUpdate(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(self::any())
            ->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(true);

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

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }

    public function testWhenNotEnabled(): void
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(self::any())
            ->method('isChanged')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(true);

        $event->expects(self::any())
            ->method('getNewValue')
            ->with(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME)
            ->willReturn(42);

        $this->messageProducer
            ->expects(self::never())
            ->method(self::anything());

        $this->webCatalogConfigChangeListener->setEnabled(false);
        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }
}
