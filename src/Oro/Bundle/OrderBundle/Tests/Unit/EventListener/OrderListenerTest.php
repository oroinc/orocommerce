<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\ORM\OrderListener;

class OrderListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityAwareGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var OrderListener */
    private $listener;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(EntityAwareGeneratorInterface::class);

        $this->listener = new OrderListener($this->generator);
    }

    public function testPostPersist()
    {
        $newId = 125;
        $this->generator->expects($this->once())
            ->method('generate')
            ->willReturn($newId);

        $orderMock = $this->createMock(Order::class);
        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);
        $em = $this->createMock(EntityManager::class);
        $lifecycleEventArgs->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($em);
        $unitOfWork = $this->createMock(UnitOfWork::class);
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

        $orderMock = $this->createMock(Order::class);
        $orderMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn(125);

        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);
        $lifecycleEventArgs->expects($this->never())
            ->method('getObjectManager');

        $this->listener->postPersist($orderMock, $lifecycleEventArgs);
    }
}
