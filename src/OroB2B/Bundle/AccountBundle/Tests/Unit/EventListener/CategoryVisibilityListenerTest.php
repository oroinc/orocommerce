<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;

use OroB2B\Bundle\AccountBundle\EventListener\CategoryVisibilityListener;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CategoryVisibilityStorage
     */
    protected $categoryVisibilityStorage;

    /**
     * @var CategoryVisibilityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->categoryVisibilityStorage = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage'
        )->disableOriginalConstructor()->getMock();

        $this->listener = new CategoryVisibilityListener($this->categoryVisibilityStorage);
    }

    protected function tearDown()
    {
        unset($this->categoryVisibilityStorage, $this->listener);
    }

    public function testDefaults()
    {
        $this->assertAttributeEquals(false, 'invalidateAll', $this->listener);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs
     * @param bool $invalidateAll
     * @dataProvider lifecycleEventArgsDataProvider
     */
    public function testPostPersist($lifecycleEventArgs, $invalidateAll)
    {
        $this->listener->postPersist($lifecycleEventArgs);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs
     * @param bool $invalidateAll
     * @dataProvider postUpdateDataProvider
     */
    public function testPostUpdate($lifecycleEventArgs, $invalidateAll)
    {
        $this->listener->postUpdate($lifecycleEventArgs);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs
     * @param bool $invalidateAll
     * @dataProvider lifecycleEventArgsDataProvider
     */
    public function testPostRemove($lifecycleEventArgs, $invalidateAll)
    {
        $this->listener->postRemove($lifecycleEventArgs);
        $this->assertAttributeEquals($invalidateAll, 'invalidateAll', $this->listener);
    }

    public function testOnPostFlush()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OnFlushEventArgs $onFlushEventArgs */
        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|PostFlushEventArgs $postFlushEventArgs */
        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertAttributeEquals(0, 'flushCounter', $this->listener);

        $this->listener->onFlush($onFlushEventArgs);
        $this->assertAttributeEquals(1, 'flushCounter', $this->listener);

        $this->listener->onFlush($onFlushEventArgs);
        $this->assertAttributeEquals(2, 'flushCounter', $this->listener);

        $this->listener->postPersist($this->getLifecycleEventArgs(new Category()));
        $this->assertAttributeEquals(true, 'invalidateAll', $this->listener);

        $this->listener->postFlush($postFlushEventArgs);
        $this->assertAttributeEquals(1, 'flushCounter', $this->listener);
        $this->assertAttributeEquals(true, 'invalidateAll', $this->listener);

        $this->categoryVisibilityStorage->expects($this->once())
            ->method('clearData')
            ->willReturnCallback(function (array $accountIds = null) {
                $this->assertNull($accountIds);
            });
        $this->listener->postFlush($postFlushEventArgs);
        $this->assertAttributeEquals(0, 'flushCounter', $this->listener);
        $this->assertAttributeEquals(false, 'invalidateAll', $this->listener);
    }

    public function testOnClear()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OnClearEventArgs $onClearEventArgs */
        $onClearEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnClearEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener->postPersist($this->getLifecycleEventArgs(new Category()));
        $this->assertAttributeEquals(true, 'invalidateAll', $this->listener);

        $this->listener->onClear($onClearEventArgs);

        $this->assertAttributeEquals(false, 'invalidateAll', $this->listener);
    }

    /**
     * @return array
     */
    public function lifecycleEventArgsDataProvider()
    {
        return [
            'category' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new Category()),
                'invalidateAll' => true,
            ],
            'other' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new \stdClass()),
                'invalidateAll' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function postUpdateDataProvider()
    {
        $changedParent = new Category();
        $changedNonParent = new Category();

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->exactly(2))
            ->method('getEntityChangeSet')
            ->willReturnMap(
                [
                    [$changedParent, ['parentCategory' => 1]],
                    [$changedNonParent, ['nonParentCategory' => 1]],
                ]
            );
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(2))
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $changedParentLifecycleEventArgs = $this->getLifecycleEventArgs($changedParent);
        $changedParentLifecycleEventArgs->expects($this->exactly(1))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $changedNonParentLifecycleEventArgs = $this->getLifecycleEventArgs($changedNonParent);
        $changedNonParentLifecycleEventArgs->expects($this->exactly(1))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        return [
            'category changed parent' => [
                'lifecycleEventArgs' => $changedParentLifecycleEventArgs,
                'invalidateAll' => true,
            ],
            'category changed non parent' => [
                'lifecycleEventArgs' => $changedNonParentLifecycleEventArgs,
                'invalidateAll' => false,
            ],
            'other' => [
                'lifecycleEventArgs' => $this->getLifecycleEventArgs(new \stdClass()),
                'invalidateAll' => false,
            ],
        ];
    }

    /**
     * @param object $entity
     * @return \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs
     */
    protected function getLifecycleEventArgs($entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs */
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        return $lifecycleEventArgs;
    }
}
