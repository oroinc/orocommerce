<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\EventListener\NavigationRootConfigChangeListener;

class NavigationRootConfigChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $layoutCacheProvider;

    /** @var NavigationRootConfigChangeListener */
    private $configListener;

    protected function setUp()
    {
        $this->layoutCacheProvider = $this->createMock(CacheProvider::class);
        $this->configListener = new NavigationRootConfigChangeListener($this->layoutCacheProvider);
    }

    public function testOnConfigUpdate()
    {
        $event = new ConfigUpdateEvent(['oro_web_catalog.navigation_root' => ['old' => 1, 'new' => 2]]);
        $this->layoutCacheProvider->expects($this->once())
            ->method('deleteAll');
        $this->configListener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateOtherSetting()
    {
        $event = new ConfigUpdateEvent(['some_other_setting_changed' => ['old' => 1, 'new' => 2]]);
        $this->layoutCacheProvider->expects($this->never())
            ->method('deleteAll');
        $this->configListener->onConfigUpdate($event);
    }
}
