<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderListener;

class OrderListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityAwareGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $generator;

    /**
     * @var OrderListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->generator = $this->getMock('Oro\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface');

        $this->listener = new OrderListener($this->generator);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->generator, $this->listener);
    }

    public function testPostPersist()
    {
        $newId = 125;
        $this->generator->expects($this->once())
            ->method('generate')
            ->willReturn($newId);

        /** @var Order|\PHPUnit_Framework_MockObject_MockObject $orderMock */
        $orderMock = $this->getMock('Oro\Bundle\OrderBundle\Entity\Order');
        $lifecycleEventArgs = $this->getLifecycleEventArgs();
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);
        /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject $unitOfWork */
        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($orderMock, [
                'identifier' => [null, $newId],
            ]);

        $this->listener->postPersist($orderMock, $lifecycleEventArgs);
    }

    public function testPostPersistOrderWithIdentifier()
    {
        $this->generator->expects($this->never())
            ->method('generate');

        /** @var Order|\PHPUnit_Framework_MockObject_MockObject $orderMock */
        $orderMock = $this->getMock('Oro\Bundle\OrderBundle\Entity\Order');
        $orderMock->expects($this->once())->method('getIdentifier')->willReturn(125);

        $lifecycleEventArgs = $this->getLifecycleEventArgs();
        $lifecycleEventArgs->expects($this->never())
            ->method('getEntityManager');

        $this->listener->postPersist($orderMock, $lifecycleEventArgs);
    }

    /**
     * @return LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLifecycleEventArgs()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $lifecycleEventArgs */
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        return $lifecycleEventArgs;
    }
}
