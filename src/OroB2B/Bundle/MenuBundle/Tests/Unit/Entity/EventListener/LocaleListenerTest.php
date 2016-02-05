<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Entity\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\MenuBundle\EventListener\LocaleListener;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocaleListener
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

        $this->listener = new LocaleListener($menuProviderLink);

        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testPostPersist()
    {
        $entity = new Locale();
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->once())
            ->method('rebuildCacheByLocale')
            ->with($entity);

        $this->listener->postPersist($event);
    }

    public function testPostPersistWithWrongEntity()
    {
        $entity = new \stdClass;
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->never())
            ->method('rebuildCacheByLocale');

        $this->listener->postPersist($event);
    }

    public function testPostUpdate()
    {
        $entity = new Locale();
        $event = $this->getEventMock($entity);
        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($entity)
            ->willReturn(['parentLocale' => 'test']);
        $this->objectManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->menuProvider->expects($this->once())
            ->method('rebuildCacheByLocale')
            ->with($entity);

        $this->listener->postUpdate($event);
    }

    public function testPostUpdateWithWrongEntity()
    {
        $entity = new \stdClass;
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->never())
            ->method('rebuildCacheByLocale');

        $this->listener->postUpdate($event);
    }

    public function testPostRemove()
    {
        $entity = new Locale();
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->once())
            ->method('clearCacheByLocale')
            ->with($entity);

        $this->listener->postRemove($event);
    }

    public function testPostRemoveWithWrongEntity()
    {
        $entity = new \stdClass;
        $event = $this->getEventMock($entity);

        $this->menuProvider->expects($this->never())
            ->method('clearCacheByLocale');

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
