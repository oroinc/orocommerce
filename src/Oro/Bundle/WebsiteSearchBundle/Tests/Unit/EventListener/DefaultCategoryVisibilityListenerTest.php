<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\CustomerBundle\Visibility\Resolver\CategoryVisibilityResolver;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\DefaultCategoryVisibilityListener;

class DefaultCategoryVisibilityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultCategoryVisibilityListener
     */
    protected $listener;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    protected function setUp()
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
