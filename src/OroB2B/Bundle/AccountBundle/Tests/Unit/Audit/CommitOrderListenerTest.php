<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Audit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Internal\CommitOrderCalculator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

use OroB2B\Bundle\AccountBundle\Audit\CommitOrderListener;

class CommitOrderListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CommitOrderListener */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new CommitOrderListener();
    }

    public function testEmptyDependencies()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->never())->method('getClassMetadata');

        $event = new OnFlushEventArgs($em);

        $this->listener->onFlush($event);
    }

    public function testDependencies()
    {
        $class1 = '\stdClass1';
        $class2 = '\stdClass2';
        $class3 = '\stdClass3';

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');

        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $uow */
        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')->disableOriginalConstructor()->getMock();

        $em->expects($this->once())->method('getUnitOfWork')->willReturn($uow);

        $calc = new CommitOrderCalculator();
        $uow->expects($this->once())->method('getCommitOrderCalculator')->willReturn($calc);

        $metadata1 = new ClassMetadata($class1);
        $calc->addClass($metadata1);
        $metadata2 = new ClassMetadata($class2);
        $calc->addClass($metadata2);
        $metadata3 = new ClassMetadata($class3);
        $calc->addClass($metadata3);

        $em->expects($this->atLeastOnce())->method('getClassMetadata')
            ->willReturnMap(
                [
                    [$class1, $metadata1],
                    [$class2, $metadata2],
                    [$class3, $metadata3],
                ]
            );

        $event = new OnFlushEventArgs($em);
        $this->listener->addDependency($class3, $class2);
        $this->listener->addDependency($class3, $class1);
        $this->listener->addDependency($class2, $class1);
        $this->listener->onFlush($event);

        $this->assertEquals([$metadata3, $metadata2, $metadata1], $calc->getCommitOrder());
    }
}
