<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtension;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ProductKitItemLineItemDeleteHandler;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitItemLineItemDeleteHandlerTest extends TestCase
{
    private EntityManagerInterface|MockObject $em;

    private ShoppingListTotalManager|MockObject $totalManager;

    private ProductKitItemLineItemDeleteHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->totalManager = $this->createMock(ShoppingListTotalManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ProductKitItemLineItem::class)
            ->willReturn($this->em);

        $accessDeniedExceptionFactory = new EntityDeleteAccessDeniedExceptionFactory();

        $extension = new EntityDeleteHandlerExtension();
        $extension->setDoctrine($doctrine);
        $extension->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects(self::any())
            ->method('getHandlerExtension')
            ->with(ProductKitItemLineItem::class)
            ->willReturn($extension);

        $this->handler = new ProductKitItemLineItemDeleteHandler($this->totalManager);
        $this->handler->setDoctrine($doctrine);
        $this->handler->setAccessDeniedExceptionFactory($accessDeniedExceptionFactory);
        $this->handler->setExtensionRegistry($extensionRegistry);
    }

    public function testDelete(): void
    {
        $productKitItemLineItem = new ProductKitItemLineItem();

        $lineItem = new LineItem();
        $lineItem->addKitItemLineItem($productKitItemLineItem);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        self::assertCount(1, $lineItem->getKitItemLineItems());

        $this->em->expects(self::once())
            ->method('remove')
            ->with($this->identicalTo($productKitItemLineItem));
        $this->em->expects(self::once())
            ->method('flush');

        $this->totalManager->expects(self::once())
            ->method('recalculateTotals')
            ->with($this->identicalTo($shoppingList), $this->isFalse());

        self::assertNull(
            $this->handler->delete($productKitItemLineItem)
        );

        self::assertCount(0, $lineItem->getKitItemLineItems());
    }

    public function testDeleteWithoutFlush(): void
    {
        $productKitItemLineItem = new ProductKitItemLineItem();

        $lineItem = new LineItem();
        $lineItem->addKitItemLineItem($productKitItemLineItem);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        self::assertCount(1, $lineItem->getKitItemLineItems());

        $this->em->expects(self::once())
            ->method('remove')
            ->with($this->identicalTo($productKitItemLineItem));
        $this->em->expects(self::never())
            ->method('flush');

        $this->totalManager->expects(self::never())
            ->method('recalculateTotals');

        self::assertEquals(
            ['entity' => $productKitItemLineItem],
            $this->handler->delete($productKitItemLineItem, false)
        );

        self::assertCount(0, $lineItem->getKitItemLineItems());
    }

    public function testFlush(): void
    {
        $productKitItemLineItem = new ProductKitItemLineItem();

        $lineItem = new LineItem();
        $lineItem->addKitItemLineItem($productKitItemLineItem);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        self::assertCount(1, $lineItem->getKitItemLineItems());

        $this->em->expects(self::once())
            ->method('flush');

        $this->totalManager->expects(self::once())
            ->method('recalculateTotals')
            ->with($this->identicalTo($shoppingList), $this->isFalse());

        $this->handler->flush(['entity' => $productKitItemLineItem]);
    }

    public function testFlushAll(): void
    {
        $productKitItemLineItem1 = new ProductKitItemLineItem();
        $productKitItemLineItem2 = new ProductKitItemLineItem();
        $productKitItemLineItem3 = new ProductKitItemLineItem();

        $lineItem1 = new LineItem();
        $lineItem1->addKitItemLineItem($productKitItemLineItem1);
        $lineItem2 = new LineItem();
        $lineItem2->addKitItemLineItem($productKitItemLineItem2);
        $lineItem3 = new LineItem();
        $lineItem3->addKitItemLineItem($productKitItemLineItem3);

        $shoppingList1 = new ShoppingList();
        $shoppingList1->addLineItem($lineItem1);
        $shoppingList1->addLineItem($lineItem2);

        $shoppingList2 = new ShoppingList();
        $shoppingList2->addLineItem($lineItem3);

        $this->em->expects(self::once())
            ->method('flush');

        $this->totalManager->expects(self::exactly(2))
            ->method('recalculateTotals')
            ->withConsecutive(
                [$this->identicalTo($shoppingList1), $this->isFalse()],
                [$this->identicalTo($shoppingList2), $this->isFalse()]
            );

        $this->handler->flushAll([
            ['entity' => $productKitItemLineItem1],
            ['entity' => $productKitItemLineItem2],
            ['entity' => $productKitItemLineItem3]
        ]);
    }
}
