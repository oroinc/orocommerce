<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtension;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Handler\OrderDiscountDeleteHandler;
use Oro\Bundle\OrderBundle\Total\TotalHelper;

class OrderDiscountDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var TotalHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $totalHelper;

    /** @var OrderDiscountDeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->totalHelper = $this->createMock(TotalHelper::class);

        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(OrderDiscount::class)
            ->willReturn($this->em);

        $accessDeniedExceptionFactory = new EntityDeleteAccessDeniedExceptionFactory();

        $extension = new EntityDeleteHandlerExtension();
        $extension->setDoctrine($doctrine);
        $extension->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects($this->any())
            ->method('getHandlerExtension')
            ->with(OrderDiscount::class)
            ->willReturn($extension);

        $this->handler = new OrderDiscountDeleteHandler(
            $this->totalHelper
        );
        $this->handler->setDoctrine($doctrine);
        $this->handler->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $this->handler->setExtensionRegistry($extensionRegistry);
    }

    public function testDelete()
    {
        $discount = new OrderDiscount();
        $order = new Order();
        $order->addDiscount($discount);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($discount));
        $this->em->expects($this->once())
            ->method('flush');

        $this->totalHelper->expects($this->once())
            ->method('fill')
            ->with($this->identicalTo($order));

        $this->assertNull(
            $this->handler->delete($discount)
        );

        $this->assertCount(0, $order->getDiscounts());
    }

    public function testDeleteWithoutFlush()
    {
        $discount = new OrderDiscount();
        $order = new Order();
        $order->addDiscount($discount);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($discount));
        $this->em->expects($this->never())
            ->method('flush');

        $this->totalHelper->expects($this->never())
            ->method('fill');

        $this->assertEquals(
            ['entity' => $discount],
            $this->handler->delete($discount, false)
        );

        $this->assertCount(0, $order->getDiscounts());
    }

    public function testFlush()
    {
        $discount = new OrderDiscount();
        $order = new Order();
        $order->addDiscount($discount);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalHelper->expects($this->once())
            ->method('fill')
            ->with($this->identicalTo($order));

        $this->handler->flush(['entity' => $discount]);
    }

    public function testFlushAll()
    {
        $discount1 = new OrderDiscount();
        $discount2 = new OrderDiscount();
        $order1 = new Order();
        $order1->addDiscount($discount1);
        $order1->addDiscount($discount2);

        $discount3 = new OrderDiscount();
        $order2 = new Order();
        $order2->addDiscount($discount3);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalHelper->expects($this->exactly(2))
            ->method('fill')
            ->withConsecutive(
                [$this->identicalTo($order1)],
                [$this->identicalTo($order2)]
            );

        $this->handler->flushAll([
            ['entity' => $discount1],
            ['entity' => $discount2],
            ['entity' => $discount3]
        ]);
    }
}
