<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolver;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\DefaultCategoryVisibilityListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DefaultCategoryVisibilityListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DefaultCategoryVisibilityListener
     */
    protected $listener;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();
        $this->listener = new DefaultCategoryVisibilityListener($this->eventDispatcher);
        $this->event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnUpdateAfterWithChanges()
    {
        $this->event->expects($this->once())
            ->method('isChanged')
            ->with(CategoryVisibilityResolver::OPTION_CATEGORY_VISIBILITY)
            ->willReturn(true);

        $reindexationEvent = new ReindexationRequestEvent();
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ReindexationRequestEvent::EVENT_NAME, $reindexationEvent);

        $this->listener->onUpdateAfter($this->event);
    }

    public function testOnUpdateAfterWithoutChanges()
    {
        $this->event->expects($this->once())
            ->method('isChanged')
            ->with(CategoryVisibilityResolver::OPTION_CATEGORY_VISIBILITY)
            ->willReturn(false);

        $reindexationEvent = new ReindexationRequestEvent();
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch')
            ->with(ReindexationRequestEvent::EVENT_NAME, $reindexationEvent);

        $this->listener->onUpdateAfter($this->event);
    }
}
