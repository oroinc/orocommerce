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

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new DefaultVisibilityChangedListener($this->eventDispatcher);
    }

    public function testOnConfigUpdateWhenCategoryDefaultVisibilityWasChanged()
    {
        $event = new ConfigUpdateEvent(
            ['oro_visibility.category_visibility' => ['old' => 1, 'new' => 2]],
            'global',
            0
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([Product::class]), ReindexationRequestEvent::EVENT_NAME);

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateWhenProductDefaultVisibilityWasChanged()
    {
        $event = new ConfigUpdateEvent(
            ['oro_visibility.product_visibility' => ['old' => 1, 'new' => 2]],
            'global',
            0
        );

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([Product::class]), ReindexationRequestEvent::EVENT_NAME);

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateWhenDefaultVisibilitiesWereNotChanged()
    {
        $event = new ConfigUpdateEvent([], 'global', 0);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->onConfigUpdate($event);
    }
}
