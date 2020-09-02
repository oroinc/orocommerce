<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListLineItemsCountListener;
use Oro\Component\Testing\Unit\EntityTrait;

class ShoppingListLineItemsCountListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var UnitOfWork */
    private $unitOfWork;

    /** @var ShoppingListRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ShoppingListLineItemsCountListener */
    private $listener;

    protected function setUp(): void
    {
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->repository = $this->createMock(ShoppingListRepository::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->repository);

        $this->listener = new ShoppingListLineItemsCountListener();
    }

    public function testFlushEvents(): void
    {
        $shoppingList1 = $this->getEntity(ShoppingList::class, ['id' => 1001]);
        $shoppingList2 = $this->getEntity(ShoppingList::class, ['id' => null]);
        $shoppingList3 = $this->getEntity(ShoppingList::class, ['id' => 1002]);
        $shoppingList4 = $this->getEntity(ShoppingList::class, ['id' => 1003]);

        $lineItem1 = $this->getEntity(LineItem::class, ['id' => 2001, 'shoppingList' => $shoppingList1]);
        $lineItem2 = $this->getEntity(LineItem::class, ['id' => 2002, 'shoppingList' => $shoppingList2]);
        $lineItem3 = $this->getEntity(LineItem::class, ['id' => null, 'shoppingList' => $shoppingList3]);
        $lineItem4 = $this->getEntity(LineItem::class, ['id' => 2003, 'shoppingList' => $shoppingList3]);
        $lineItem5 = $this->getEntity(LineItem::class, ['id' => 2004, 'shoppingList' => $shoppingList4]);

        $this->unitOfWork->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$shoppingList1, $lineItem1, $lineItem3, new \stdClass()]);

        $this->unitOfWork->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$lineItem2, new \stdClass(), $shoppingList3]);

        $this->unitOfWork->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$lineItem4, $lineItem5]);

        $this->unitOfWork->expects($this->any())
            ->method('getEntityChangeSet')
            ->willReturnMap(
                [
                    [$lineItem1, []],
                    [$lineItem2, []],
                    [$lineItem3, []],
                    [$lineItem4, ['shoppingList' => [$shoppingList1, $shoppingList4]]],
                    [$lineItem5, ['shoppingList' => [null, $shoppingList4]]],
                ]
            );

        $this->repository->expects($this->once())
            ->method('getLineItemsCount')
            ->with([$shoppingList1, $shoppingList3, $shoppingList4])
            ->willReturn([1001 => 500, 1003 => 42]);

        $this->repository->expects($this->exactly(3))
            ->method('setLineItemsCount')
            ->withConsecutive(
                [$shoppingList1, 500],
                [$shoppingList3, 0],
                [$shoppingList4, 42]
            );

        $this->listener->onFlush(new OnFlushEventArgs($this->entityManager));
        $this->listener->postFlush(new PostFlushEventArgs($this->entityManager));
    }
}
