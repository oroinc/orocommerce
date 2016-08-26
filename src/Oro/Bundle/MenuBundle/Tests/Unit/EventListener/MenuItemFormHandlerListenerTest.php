<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\EventListener;

use Oro\Bundle\MenuBundle\Entity\MenuItem;
use Oro\Bundle\MenuBundle\EventListener\MenuItemFormHandlerListener;
use Oro\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class MenuItemFormHandlerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MenuItemFormHandlerListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DatabaseMenuProvider
     */
    protected $menuProvider;

    public function setUp()
    {
        $this->menuProvider = $this->getMockBuilder('Oro\Bundle\MenuBundle\Menu\DatabaseMenuProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MenuItemFormHandlerListener($this->menuProvider);
    }

    /**
     * @dataProvider afterEntityFlushDataProvider
     *
     * @param MenuItem $entity
     * @param bool $hasParent
     */
    public function testAfterEntityFlush(MenuItem $entity, $hasParent)
    {
        $event = $this->getMockBuilder('Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($entity);

        $this->menuProvider->expects($hasParent ? $this->once() : $this->never())
            ->method('rebuildCacheByMenuItem')
            ->with($entity)
        ;
        $this->listener->afterEntityFlush($event);
    }

    /**
     * @return array
     */
    public function afterEntityFlushDataProvider()
    {
        return [
            [
                $entity = (new MenuItem())->setParent(new MenuItem()),
                true
            ],
            [
                $entity = new MenuItem(),
                false
            ]
        ];
    }
}
