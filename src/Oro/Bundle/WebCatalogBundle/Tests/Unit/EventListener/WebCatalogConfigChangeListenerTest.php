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
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new WebCatalogConfigChangeListener($this->dispatcher);
    }

    public function testOnConfigurationUpdate(): void
    {
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([], [], [], true, ['main']), ReindexationRequestEvent::EVENT_NAME);

        $this->listener->onConfigurationUpdate(
            new ConfigUpdateEvent(['oro_web_catalog.web_catalog' => ['old' => 1, 'new' => 2]], 'website', 1)
        );
    }

    public function testOnOtherConfigurationUpdate(): void
    {
        $event = new ConfigUpdateEvent([], 'website', 1);

        $this->dispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->onConfigurationUpdate($event);
    }
}
