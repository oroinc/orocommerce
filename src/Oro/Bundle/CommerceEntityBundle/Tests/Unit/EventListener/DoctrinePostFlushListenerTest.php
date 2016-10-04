<?php

namespace Oro\Bundle\CommerceEntityBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\CommerceEntityBundle\EventListener\DoctrinePostFlushListener;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorage;
use Oro\Bundle\CommerceEntityBundle\Tests\Stub\Entity1;
use Oro\Bundle\CommerceEntityBundle\Tests\Stub\Entity2;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DoctrinePostFlushListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostFlush()
    {
        $registry = $this->getRegistryMock();

        $testEntity1 = new Entity1();
        $testEntity2 = new Entity1();
        $testEntity3 = new Entity2();

        $storage = $this->getStorage();
        $storage->scheduleForExtraInsert($testEntity1);
        $storage->scheduleForExtraInsert($testEntity2);
        $storage->scheduleForExtraInsert($testEntity3);

        $em1 = $this->getEntityManagerMock();
        $em2 = $this->getEntityManagerMock();

        //method 'persist' should be called once for every entity
        $em1->expects($this->at(0))
            ->method('persist')
            ->with($testEntity1);
        $em1->expects($this->at(1))
            ->method('persist')
            ->with($testEntity2);
        $em2->expects($this->once())
            ->method('persist')
            ->with($testEntity3);

        //method 'flush' should be called only once for every manager
        $em1->expects($this->once())
            ->method('flush');
        $em2->expects($this->once())
            ->method('flush');

        $registry->expects($this->at(0))
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($testEntity1))
            ->willReturn($em1);

        $registry->expects($this->at(1))
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($testEntity2))
            ->willReturn($em1);

        $registry->expects($this->at(2))
            ->method('getManagerForClass')
            ->with(ClassUtils::getClass($testEntity3))
            ->willReturn($em2);


        $doctrineHelper = $this->getDoctrineHelper($registry);
        $listener = new DoctrinePostFlushListener($doctrineHelper, $storage);
        $listener->postFlush();
        $this->assertEmpty($storage->getScheduledForInsert());
    }

    public function testPostFlushDisabled()
    {
        $testEntity = new Entity1();
        $storage = $this->getStorage();
        $storage->scheduleForExtraInsert($testEntity);

        $listener = new DoctrinePostFlushListener($this->getDoctrineHelper(), $storage);
        $listener->setEnabled(false);
        $listener->postFlush();
        $this->assertNotEmpty($storage->getScheduledForInsert());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManagerMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|Registry $registry
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper(Registry $registry = null)
    {
        $registry = $registry ?: $this->getRegistryMock();
        return new DoctrineHelper($registry);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected function getRegistryMock()
    {
        return $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ExtraActionEntityStorage
     */
    protected function getStorage()
    {
        return new ExtraActionEntityStorage();
    }
}
