<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolver;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;
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

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->getMock();
        $this->listener = new DefaultCategoryVisibilityListener($this->eventDispatcher);
    }

    public function testOnUpdateAfterWithChanges()
    {
        $event = new ConfigUpdateEvent([
            CategoryVisibilityResolver::OPTION_CATEGORY_VISIBILITY => [
                'new' => 'new',
                'old' => 'old',
            ]
        ]);

        $reindexationEvent = new ReindexationTriggerEvent();
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ReindexationTriggerEvent::EVENT_NAME, $reindexationEvent);

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterWithoutChanges()
    {
        $event = new ConfigUpdateEvent([]);

        $reindexationEvent = new ReindexationTriggerEvent();
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch')
            ->with(ReindexationTriggerEvent::EVENT_NAME, $reindexationEvent);

        $this->listener->onUpdateAfter($event);
    }
}
