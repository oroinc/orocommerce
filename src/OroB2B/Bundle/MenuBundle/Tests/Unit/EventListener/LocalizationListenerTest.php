<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\LocaleBundle\Entity\Localization;

use OroB2B\Bundle\MenuBundle\EventListener\LocalizationListener;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class LocalizationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizationListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DatabaseMenuProvider
     */
    protected $menuProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected $objectManager;

    public function setUp()
    {
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

        $this->listener = new LocalizationListener($menuProviderLink);

        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testPostPersist()
    {
        $entity = new Localization();
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->once())
            ->method('rebuildCacheByLocalization')
            ->with($entity);

        $this->listener->postPersist($event);
    }

    public function testPostPersistWithWrongEntity()
    {
        $entity = new \stdClass;
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->never())
            ->method('rebuildCacheByLocalization');

        $this->listener->postPersist($event);
    }

    public function testPostUpdate()
    {
        $entity = new Localization();
        $event = $this->getEventMock($entity);
        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($entity)
            ->willReturn(['parentLocalization' => 'test']);
        $this->objectManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->menuProvider->expects($this->once())
            ->method('rebuildCacheByLocalization')
            ->with($entity);

        $this->listener->postUpdate($event);
    }

    public function testPostUpdateWithWrongEntity()
    {
        $entity = new \stdClass;
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->never())
            ->method('rebuildCacheByLocalization');

        $this->listener->postUpdate($event);
    }

    public function testPostRemove()
    {
        $entity = new Localization();
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->once())
            ->method('clearCacheByLocalization')
            ->with($entity);

        $this->listener->postRemove($event);
    }

    public function testPostRemoveWithWrongEntity()
    {
        $entity = new \stdClass;
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->never())
            ->method('clearCacheByLocalization');

        $this->listener->postPersist($event);
    }

    /**
     * @param object $entity
     * @return LifecycleEventArgs
     */
    protected function getEventMock($entity)
    {
        return new LifecycleEventArgs($entity, $this->objectManager);
    }
}
