<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListTotalsDemoDataFixturesListener;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;

class ShoppingListTotalsDemoDataFixturesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListrepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ShoppingListTotalManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListTotalManager;

    /** @var ShoppingListTotalsDemoDataFixturesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ShoppingListRepository::class);

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($this->manager);

        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);

        $this->listener = new ShoppingListTotalsDemoDataFixturesListener($registry, $this->shoppingListTotalManager);
    }

    public function testOnPostLoadIsDemoFixtures(): void
    {
        $shoppingList1 = new ShoppingListStub();
        $shoppingList1->setId(1001);

        $shoppingList2 = new ShoppingListStub();
        $shoppingList2->setId(2002);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([$shoppingList1, $shoppingList2]);

        $this->shoppingListTotalManager->expects($this->exactly(2))
            ->method('recalculateTotals')
            ->withConsecutive(
                [$shoppingList1, true],
                [$shoppingList2, true]
            );

        $this->listener->onPostLoad(
            new MigrationDataFixturesEvent($this->manager, DataFixturesExecutorInterface::DEMO_FIXTURES)
        );
    }

    public function testOnPostLoadIsNotDemoFixtures(): void
    {
        $this->repository->expects($this->never())
            ->method($this->anything());

        $this->shoppingListTotalManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onPostLoad(
            new MigrationDataFixturesEvent($this->manager, DataFixturesExecutorInterface::MAIN_FIXTURES)
        );
    }
}
