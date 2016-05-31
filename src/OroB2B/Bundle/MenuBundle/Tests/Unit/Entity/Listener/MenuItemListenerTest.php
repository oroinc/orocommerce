<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Entity\Listener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
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

        $menuProviderLink = $this
            ->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->setMethods(['getService'])
            ->disableOriginalConstructor()
            ->getMock();

        $menuProviderLink->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($this->menuProvider));

        $this->listener = new MenuItemListener($menuProviderLink);

        $this->entity = new MenuItem();

        $this->event = $this->getEventMock($this->entity);
    }

    public function testPostRemoveWithRegularMenuItem()
    {
        $this->entity = new MenuItem();
        $this->entity->setParent(new MenuItem());
        $this->assertCacheRebuild();

        $this->event = $this->getEventMock($this->entity);
        $this->menuProvider->expects($this->never())
            ->method('clearCacheByAlias');
        $this->listener->postRemove($this->entity, $this->event);
    }

    public function testPostRemove()
    {
        $alias = 'test_menu';
        $menuItem = new MenuItem();
        $title = new LocalizedFallbackValue();
        $title->setString($alias);
        $menuItem->addTitle($title);
        $this->menuProvider->expects($this->once())
            ->method('clearCacheByAlias')
            ->with($alias);
        $this->listener->postRemove($menuItem, $this->getEventMock($menuItem));
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

        $this->menuProvider->expects($this->once())
            ->method('rebuildCacheByMenuItem')
            ->with($this->entity);
    }
}
