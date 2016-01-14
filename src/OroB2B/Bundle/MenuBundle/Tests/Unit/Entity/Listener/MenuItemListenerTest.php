<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Entity\Listener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\MenuBundle\Entity\Listener\MenuItemListener;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class MenuItemListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ROOT_ID = 23;
    const ROOT_TITLE = 'root_title';

    /**
     * @var MenuItemListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DatabaseMenuProvider
     */
    protected $menuProvider;

    /**
     * @var MenuItem
     */
    protected $entity;

    /**
     * @var LifecycleEventArgs
     */
    protected $event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected $objectManager;

    public function setUp()
    {
        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->menuProvider = $this->getMockBuilder('OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new MenuItemListener($this->menuProvider);

        $this->entity = new MenuItem();

        $this->event = $this->getEventMock($this->entity);
    }

    public function testPostUpdate()
    {
        $this->assertCacheRebuild();
        $this->listener->postUpdate($this->entity, $this->event);
    }

    public function testPostUpdateWithoutRootId()
    {
        $this->objectManager->expects($this->never())
            ->method('find');
        $this->listener->postUpdate($this->entity, $this->event);
    }

    public function testPostPersist()
    {
        $this->assertCacheRebuild();
        $this->listener->postPersist($this->entity, $this->event);
    }

    public function testPostPersistWithoutRootId()
    {
        $this->objectManager->expects($this->never())
            ->method('find');
        $this->listener->postUpdate($this->entity, $this->event);
    }

    /**
     * @param $entity
     * @return LifecycleEventArgs
     */
    protected function getEventMock($entity)
    {
        return new LifecycleEventArgs($entity, $this->objectManager);
    }

    protected function assertCacheRebuild()
    {
        $this->entity->setRoot(self::ROOT_ID);
        /** @var MenuItem $root */
        $root = $this->getEntity('OroB2B\Bundle\MenuBundle\Entity\MenuItem', ['id' => self::ROOT_ID]);
        $root->addTitle((new LocalizedFallbackValue)->setString(self::ROOT_TITLE));

        $this->objectManager->expects($this->once())
            ->method('find')
            ->with('OroB2BMenuBundle:MenuItem', self::ROOT_ID)
            ->willReturn($root);

        $this->menuProvider->expects($this->once())
            ->method('rebuildCacheByAlias')
            ->with(self::ROOT_TITLE);
    }
}
