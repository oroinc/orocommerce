<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtension;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemDeleteHandler;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;

class ShoppingListLineItemDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var ShoppingListTotalManager|\PHPUnit\Framework\MockObject\MockObject */
    private $totalManager;

    /** @var ShoppingListLineItemDeleteHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->totalManager = $this->createMock(ShoppingListTotalManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($this->em);

        $accessDeniedExceptionFactory = new EntityDeleteAccessDeniedExceptionFactory();

        $extension = new EntityDeleteHandlerExtension();
        $extension->setDoctrine($doctrine);
        $extension->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects($this->any())
            ->method('getHandlerExtension')
            ->with(LineItem::class)
            ->willReturn($extension);

        $this->handler = new ShoppingListLineItemDeleteHandler($this->totalManager);
        $this->handler->setDoctrine($doctrine);
        $this->handler->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $this->handler->setExtensionRegistry($extensionRegistry);
    }

    public function testDelete()
    {
        $lineItem = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($lineItem));
        $this->em->expects($this->once())
            ->method('flush');

        $this->totalManager->expects($this->once())
            ->method('recalculateTotals')
            ->with($this->identicalTo($shoppingList), $this->isFalse());

        $this->assertNull(
            $this->handler->delete($lineItem)
        );

        $this->assertCount(0, $shoppingList->getLineItems());
    }

    public function testDeleteWithoutFlush()
    {
        $lineItem = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($lineItem));
        $this->em->expects($this->never())
            ->method('flush');

        $this->totalManager->expects($this->never())
            ->method('recalculateTotals');

        $this->assertEquals(
            ['entity' => $lineItem],
            $this->handler->delete($lineItem, false)
        );

        $this->assertCount(0, $shoppingList->getLineItems());
    }

    public function testFlush()
    {
        $lineItem = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalManager->expects($this->once())
            ->method('recalculateTotals')
            ->with($this->identicalTo($shoppingList), $this->isFalse());

        $this->handler->flush(['entity' => $lineItem]);
    }

    public function testFlushAll()
    {
        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();
        $shoppingList1 = new ShoppingList();
        $shoppingList1->addLineItem($lineItem1);
        $shoppingList1->addLineItem($lineItem2);

        $lineItem3 = new LineItem();
        $shoppingList2 = new ShoppingList();
        $shoppingList2->addLineItem($lineItem3);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalManager->expects($this->exactly(2))
            ->method('recalculateTotals')
            ->withConsecutive(
                [$this->identicalTo($shoppingList1), $this->isFalse()],
                [$this->identicalTo($shoppingList2), $this->isFalse()]
            );

        $this->handler->flushAll([
            ['entity' => $lineItem1],
            ['entity' => $lineItem2],
            ['entity' => $lineItem3]
        ]);
    }
}
