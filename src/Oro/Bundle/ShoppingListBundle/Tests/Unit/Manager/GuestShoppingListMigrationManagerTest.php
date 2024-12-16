<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPostMergeEvent;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPostMoveEvent;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPreMergeEvent;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPreMoveEvent;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\CustomerVisitorStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * unit test for guest shopping list migration manager
 */
final class GuestShoppingListMigrationManagerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ShoppingListLimitManager&MockObject $shoppingListLimitManager;
    private ShoppingListManager&MockObject $shoppingListManager;
    private CurrentShoppingListManager&MockObject $currentShoppingListManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private GuestShoppingListMigrationManager $migrationManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->migrationManager = new GuestShoppingListMigrationManager(
            $this->doctrineHelper,
            $this->shoppingListLimitManager,
            $this->shoppingListManager,
            $this->currentShoppingListManager,
            $this->eventDispatcher
        );
    }

    public function testMoveShoppingListToCustomerUser(): void
    {
        $this->shoppingListLimitManager->expects(self::once())
            ->method('isCreateEnabled')
            ->willReturn(true);

        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 1);
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());
        $shoppingList->addLineItem(new LineItem());
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);

        $customerVisitorEntityManager = $this->createMock(EntityManager::class);
        $customerVisitorEntityManager->expects(self::once())
            ->method('flush');
        $shoppingListEntityManager = $this->createMock(EntityManager::class);
        $shoppingListEntityManager->expects(self::once())
            ->method('flush');
        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityManagerForClass')
            ->willReturnMap([
                [CustomerVisitor::class, true, $customerVisitorEntityManager],
                [ShoppingList::class, true, $shoppingListEntityManager]
            ]);

        $this->currentShoppingListManager->expects(self::once())
            ->method('setCurrent')
            ->with($customerUser, $shoppingList)
            ->willReturn(true);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('hasListeners')
            ->willReturnMap([
                [ShoppingListPreMoveEvent::NAME, true],
                [ShoppingListPostMoveEvent::NAME, true]
            ]);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch');

        self::assertEquals(
            GuestShoppingListMigrationManager::OPERATION_MOVE,
            $this->migrationManager->migrateGuestShoppingList($visitor, $customerUser, $shoppingList)
        );
    }

    public function testMoveShoppingListToCustomerUserWhenCustomerUserTheSame(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($customerUser);

        $this->shoppingListManager->expects(self::never())
            ->method('bulkAddLineItems');

        $this->eventDispatcher->expects(self::never())
            ->method('hasListeners');

        self::assertEquals(
            GuestShoppingListMigrationManager::OPERATION_NONE,
            $this->migrationManager->moveShoppingListToCustomerUser(new CustomerVisitor(), $customerUser, $shoppingList)
        );
    }

    public function testMergeShoppingListWithCurrent(): void
    {
        $this->shoppingListLimitManager->expects(self::once())
            ->method('isCreateEnabled')
            ->willReturn(false);

        $customerUserShoppingList = new ShoppingList();
        $shoppingList = new ShoppingList();
        $shoppingListItem = new LineItem();
        $shoppingList->addLineItem($shoppingListItem);

        $shoppingListEntityManager = $this->createMock(EntityManager::class);
        $shoppingListEntityManager->expects(self::once())
            ->method('remove')
            ->with($shoppingList);
        $shoppingListEntityManager->expects(self::never())
            ->method('flush');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($shoppingListEntityManager);

        $this->currentShoppingListManager->expects(self::once())
            ->method('getCurrent')
            ->willReturn($customerUserShoppingList);
        $this->shoppingListManager->expects(self::once())
            ->method('bulkAddLineItems')
            ->with([$shoppingListItem], $customerUserShoppingList, GuestShoppingListMigrationManager::FLUSH_BATCH_SIZE);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('hasListeners')
            ->willReturnMap([
                [ShoppingListPreMergeEvent::NAME, true],
                [ShoppingListPostMergeEvent::NAME, true]
            ]);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch');

        self::assertEquals(
            GuestShoppingListMigrationManager::OPERATION_MERGE,
            $this->migrationManager->migrateGuestShoppingList(new CustomerVisitor(), new CustomerUser(), $shoppingList)
        );
    }

    public function testMergeShoppingListWithCurrentWhenEmptyShoppingList(): void
    {
        $this->shoppingListLimitManager->expects(self::once())
            ->method('isCreateEnabled')
            ->willReturn(false);

        $shoppingList = new ShoppingList();
        $customerUserShoppingList = new ShoppingList();

        $this->currentShoppingListManager->expects(self::once())
            ->method('getCurrent')
            ->willReturn($customerUserShoppingList);

        $this->eventDispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(ShoppingListPreMergeEvent::NAME)
            ->willReturn(false);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $shoppingListEntityManager = $this->createMock(EntityManager::class);
        $shoppingListEntityManager->expects(self::once())
            ->method('remove')
            ->with($shoppingList);
        $shoppingListEntityManager->expects(self::once())
            ->method('flush');

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($shoppingListEntityManager);

        $this->shoppingListManager->expects(self::never())
            ->method('bulkAddLineItems');

        self::assertEquals(
            GuestShoppingListMigrationManager::OPERATION_NONE,
            $this->migrationManager->migrateGuestShoppingList(new CustomerVisitor(), new CustomerUser(), $shoppingList)
        );
    }
}
