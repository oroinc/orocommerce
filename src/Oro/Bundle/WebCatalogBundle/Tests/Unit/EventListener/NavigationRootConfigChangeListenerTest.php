<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogCalculateContentNodeCacheTopic;
use Oro\Bundle\WebCatalogBundle\EventListener\NavigationRootConfigChangeListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class NavigationRootConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    private CacheProvider|\PHPUnit\Framework\MockObject\MockObject $layoutCacheProvider;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private NavigationRootConfigChangeListener $configListener;

    protected function setUp(): void
    {
        $this->layoutCacheProvider = $this->createMock(CacheProvider::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->configListener = new NavigationRootConfigChangeListener(
            $this->layoutCacheProvider,
            $this->messageProducer
        );
    }

    public function testOnConfigUpdate(): void
    {
        $event = new ConfigUpdateEvent(['oro_web_catalog.navigation_root' => ['old' => 1, 'new' => 2]], null, 1);
        $this->layoutCacheProvider->expects(self::once())
            ->method('deleteAll');
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                WebCatalogCalculateContentNodeCacheTopic::getName(),
                [WebCatalogCalculateContentNodeCacheTopic::CONTENT_NODE_ID => 2]
            );
        $this->configListener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateWithNull(): void
    {
        $event = new ConfigUpdateEvent(['oro_web_catalog.navigation_root' => ['old' => 1, 'new' => null]], null, 1);
        $this->layoutCacheProvider->expects(self::once())
            ->method('deleteAll');
        $this->messageProducer->expects(self::never())
            ->method('send');
        $this->configListener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateOtherSetting(): void
    {
        $event = new ConfigUpdateEvent(['some_other_setting_changed' => ['old' => 1, 'new' => 2]]);
        $this->layoutCacheProvider->expects(self::never())
            ->method('deleteAll');
        $this->messageProducer->expects(self::never())
            ->method('send');
        $this->configListener->onConfigUpdate($event);
    }
}
