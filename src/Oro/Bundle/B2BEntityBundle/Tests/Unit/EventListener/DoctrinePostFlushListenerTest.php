<?php

namespace OroB2B\src\Oro\Bundle\B2BEntityBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass2;

use Oro\Bundle\B2BEntityBundle\EventListener\DoctrinePostFlushListener;
use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorage;

class DoctrinePostFlushListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostFlush()
    {
        $registry = $this->getRegistryMock();

        $testEntity1 = new TestClass();
        $testEntity2 = new TestClass2();
        $storage = new ExtraActionEntityStorage();
        $storage->scheduleForExtraInsert($testEntity1);
        $storage->scheduleForExtraInsert($testEntity2);

        $em1 = $this->getEntityManagerMock();
        $em1->expects($this->once())
            ->method('persist')
            ->with($testEntity1);
        $em1->expects($this->once())
            ->method('flush');


        $em2 = $this->getEntityManagerMock();
        $em2->expects($this->once())
            ->method('persist')
            ->with($testEntity2);
        $em2->expects($this->once())
            ->method('flush');

        $registry->expects($this->at(0))
            ->method('getManagerForClass')
            ->with(get_class($testEntity1))
            ->willReturn($em1);

        $registry->expects($this->at(1))
            ->method('getManagerForClass')
            ->with(get_class($testEntity2))
            ->willReturn($em2);


        $listener = new DoctrinePostFlushListener($registry, $storage);
        $listener->postFlush();
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected function getRegistryMock()
    {
        return $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
