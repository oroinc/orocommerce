<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\EventListener\NavigationRootConfigChangeListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class NavigationRootConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $layoutCacheProvider;

    /** @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $messageProducer;

    /** @var NavigationRootConfigChangeListener */
    private $configListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->layoutCacheProvider = $this->createMock(CacheProvider::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->configListener = new NavigationRootConfigChangeListener(
            $this->layoutCacheProvider,
            $this->messageProducer
        );
    }

    public function testOnConfigUpdate()
    {
        $event = new ConfigUpdateEvent(['oro_web_catalog.navigation_root' => ['old' => 1, 'new' => 2]], null, 1);
        $this->layoutCacheProvider->expects($this->once())
            ->method('deleteAll');
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::CALCULATE_CONTENT_NODE_CACHE, ['contentNodeId' => 2]);
        $this->configListener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateWithNull()
    {
        $event = new ConfigUpdateEvent(['oro_web_catalog.navigation_root' => ['old' => 1, 'new' => null]], null, 1);
        $this->layoutCacheProvider->expects($this->once())
            ->method('deleteAll');
        $this->messageProducer->expects($this->never())
            ->method('send');
        $this->configListener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateOtherSetting()
    {
        $event = new ConfigUpdateEvent(['some_other_setting_changed' => ['old' => 1, 'new' => 2]]);
        $this->layoutCacheProvider->expects($this->never())
            ->method('deleteAll');
        $this->messageProducer->expects($this->never())
            ->method('send');
        $this->configListener->onConfigUpdate($event);
    }
}
