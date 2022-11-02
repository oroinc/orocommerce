<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\EventListener\DefaultVisibilityChangedListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DefaultVisibilityChangedListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var DefaultVisibilityChangedListener */
    private $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new DefaultVisibilityChangedListener($this->eventDispatcher);
    }

    public function testOnConfigUpdateWhenCategoryDefaultVisibilityWasChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_visibility.category_visibility')
            ->willReturn(true);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([Product::class]), ReindexationRequestEvent::EVENT_NAME);

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateWhenProductDefaultVisibilityWasChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->exactly(2))
            ->method('isChanged')
            ->willReturnMap([
                ['oro_visibility.category_visibility', false],
                ['oro_visibility.product_visibility', true]
            ]);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([Product::class]), ReindexationRequestEvent::EVENT_NAME);

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateWhenDefaultVisibilitiesWereNotChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->exactly(2))
            ->method('isChanged')
            ->willReturnMap([
                ['oro_visibility.category_visibility', false],
                ['oro_visibility.product_visibility', false]
            ]);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->onConfigUpdate($event);
    }
}
