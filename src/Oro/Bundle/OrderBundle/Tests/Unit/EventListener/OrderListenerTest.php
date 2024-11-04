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

    #[\Override]
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

        $order = new Order();
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
            ->with($order, ['identifier' => [null, $newId]]);

        $this->listener->postPersist($order, $lifecycleEventArgs);

        $this->assertEquals($newId, $order->getIdentifier());
    }

    public function testPostPersistOrderWithIdentifier()
    {
        $this->generator->expects($this->never())
            ->method('generate');

        $existingId = 125;
        $order = new Order();
        $order->setIdentifier($existingId);

        $lifecycleEventArgs = $this->createMock(LifecycleEventArgs::class);
        $lifecycleEventArgs->expects($this->never())
            ->method('getObjectManager');

        $this->listener->postPersist($order, $lifecycleEventArgs);

        $this->assertEquals($existingId, $order->getIdentifier());
    }
}
