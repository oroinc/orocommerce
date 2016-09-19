<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\DefaultCategoryVisibilityListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->listener = new DefaultCategoryVisibilityListener($this->eventDispatcher);
    }

    public function testOnUpdateAfter()
    {
        $event = new ConfigUpdateEvent([
            DefaultCategoryVisibilityListener::CATEGORY_VISIBILITY_FIELD => [
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

    public function testOnUpdateAfterEmpty()
    {
        $event = new ConfigUpdateEvent([]);

        $reindexationEvent = new ReindexationTriggerEvent();
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch')
            ->with(ReindexationTriggerEvent::EVENT_NAME, $reindexationEvent);

        $this->listener->onUpdateAfter($event);
    }
}
