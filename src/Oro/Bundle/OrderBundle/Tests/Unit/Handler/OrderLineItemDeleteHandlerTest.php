<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtension;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemDeleteHandler;
use Oro\Bundle\OrderBundle\Total\TotalHelper;

class OrderLineItemDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var TotalHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $totalHelper;

    /** @var OrderLineItemDeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->totalHelper = $this->createMock(TotalHelper::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(OrderLineItem::class)
            ->willReturn($this->em);

        $accessDeniedExceptionFactory = new EntityDeleteAccessDeniedExceptionFactory();

        $extension = new EntityDeleteHandlerExtension();
        $extension->setDoctrine($doctrine);
        $extension->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects($this->any())
            ->method('getHandlerExtension')
            ->with(OrderLineItem::class)
            ->willReturn($extension);

        $this->handler = new OrderLineItemDeleteHandler(
            $this->totalHelper
        );
        $this->handler->setDoctrine($doctrine);
        $this->handler->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $this->handler->setExtensionRegistry($extensionRegistry);
    }

    public function testDelete()
    {
        $lineItem = new OrderLineItem();
        $order = new Order();
        $order->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($lineItem));
        $this->em->expects($this->once())
            ->method('flush');

        $this->totalHelper->expects($this->once())
            ->method('fill')
            ->with($this->identicalTo($order));

        $this->assertNull(
            $this->handler->delete($lineItem)
        );

        $this->assertCount(0, $order->getLineItems());
    }

    public function testDeleteWithoutFlush()
    {
        $lineItem = new OrderLineItem();
        $order = new Order();
        $order->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($lineItem));
        $this->em->expects($this->never())
            ->method('flush');

        $this->totalHelper->expects($this->never())
            ->method('fill');

        $this->assertEquals(
            ['entity' => $lineItem],
            $this->handler->delete($lineItem, false)
        );

        $this->assertCount(0, $order->getLineItems());
    }

    public function testFlush()
    {
        $lineItem = new OrderLineItem();
        $order = new Order();
        $order->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalHelper->expects($this->once())
            ->method('fill')
            ->with($this->identicalTo($order));

        $this->handler->flush(['entity' => $lineItem]);
    }

    public function testFlushAll()
    {
        $lineItem1 = new OrderLineItem();
        $lineItem2 = new OrderLineItem();
        $order1 = new Order();
        $order1->addLineItem($lineItem1);
        $order1->addLineItem($lineItem2);

        $lineItem3 = new OrderLineItem();
        $order2 = new Order();
        $order2->addLineItem($lineItem3);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalHelper->expects($this->exactly(2))
            ->method('fill')
            ->withConsecutive(
                [$this->identicalTo($order1)],
                [$this->identicalTo($order2)]
            );

        $this->handler->flushAll([
            ['entity' => $lineItem1],
            ['entity' => $lineItem2],
            ['entity' => $lineItem3]
        ]);
    }
}
