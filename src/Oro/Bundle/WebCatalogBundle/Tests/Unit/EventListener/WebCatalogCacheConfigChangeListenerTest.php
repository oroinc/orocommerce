<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateCacheTopic;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogCacheConfigChangeListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class WebCatalogCacheConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var WebCatalogCacheConfigChangeListener */
    private $webCatalogConfigChangeListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->webCatalogConfigChangeListener = new WebCatalogCacheConfigChangeListener($this->messageProducer);
    }

    public function testOnConfigurationUpdate(): void
    {
        $webCatalogId = 42;
        $event = new ConfigUpdateEvent(
            ['oro_web_catalog.web_catalog' => ['old' => 1, 'new' => $webCatalogId]],
            'website',
            1
        );

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                WebCatalogCalculateCacheTopic::getName(),
                [WebCatalogCalculateCacheTopic::WEB_CATALOG_ID => $webCatalogId]
            );

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }

    public function testOnOtherConfigurationUpdate(): void
    {
        $event = new ConfigUpdateEvent([], 'website', 1);

        $this->messageProducer->expects(self::never())
            ->method(self::anything());

        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }

    public function testWhenNotEnabled(): void
    {
        $event = new ConfigUpdateEvent(
            ['oro_web_catalog.web_catalog' => ['old' => 1, 'new' => 42]],
            'website',
            1
        );

        $this->messageProducer->expects(self::never())
            ->method(self::anything());

        $this->webCatalogConfigChangeListener->setEnabled(false);
        $this->webCatalogConfigChangeListener->onConfigurationUpdate($event);
    }
}
