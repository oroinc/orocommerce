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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ShoppingListLineItemDeleteHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ShoppingListTotalManager&MockObject $totalManager;

    private ShoppingListLineItemDeleteHandler $handler;

    #[\Override]
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

    public function testDelete(): void
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
            ->method('invalidateAndRecalculateTotals')
            ->with($this->identicalTo($shoppingList), $this->isFalse());

        $this->assertNull(
            $this->handler->delete($lineItem)
        );

        $this->assertCount(0, $shoppingList->getLineItems());
    }

    public function testDeleteWithoutFlush(): void
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
            ->method('invalidateAndRecalculateTotals');

        $this->assertEquals(
            ['entity' => $lineItem, 'associatedList' => $shoppingList],
            $this->handler->delete($lineItem, false)
        );

        $this->assertCount(0, $shoppingList->getLineItems());
    }

    public function testDeleteSavedForLaterLineItemWithoutFlush(): void
    {
        $lineItem = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addSavedForLaterLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($this->identicalTo($lineItem));
        $this->em->expects($this->never())
            ->method('flush');

        $this->totalManager->expects($this->never())
            ->method('invalidateAndRecalculateTotals');

        $this->assertEquals(
            ['entity' => $lineItem, 'associatedList' => $shoppingList],
            $this->handler->delete($lineItem, false)
        );

        $this->assertCount(0, $shoppingList->getSavedForLaterLineItems());
    }

    public function testFlush(): void
    {
        $lineItem = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalManager->expects(self::once())
            ->method('invalidateAndRecalculateTotals')
            ->with($this->identicalTo($shoppingList), $this->isFalse());

        $this->handler->flush(['entity' => $lineItem, 'associatedList' => $shoppingList]);
    }

    public function testFlushWithoutAssociatedList(): void
    {
        $lineItem = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        $this->em->expects($this->once())
            ->method('flush');

        $this->totalManager->expects(self::never())
            ->method('invalidateAndRecalculateTotals')
            ->with($this->identicalTo($shoppingList), $this->isFalse());

        $this->handler->flush(['entity' => $lineItem]);
    }

    public function testFlushAll(): void
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
            ['entity' => $lineItem1, 'associatedList' => $shoppingList1],
            ['entity' => $lineItem2, 'associatedList' => $shoppingList2],
            ['entity' => $lineItem3, 'associatedList' => $shoppingList2]
        ]);
    }
}
