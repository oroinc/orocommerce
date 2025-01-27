<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListEventPostTransfer;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Handles logic related to migrating shopping lists from customer visitors to customer users.
 */
class GuestShoppingListMigrationManager
{
    const FLUSH_BATCH_SIZE = 100;

    public const OPERATION_NONE = 0;
    public const OPERATION_MOVE = 1;
    public const OPERATION_MERGE = 2;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ShoppingListLimitManager */
    private $shoppingListLimitManager;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ShoppingListLimitManager $shoppingListLimitManager,
        ShoppingListManager $shoppingListManager,
        CurrentShoppingListManager $currentShoppingListManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->shoppingListLimitManager = $shoppingListLimitManager;
        $this->shoppingListManager = $shoppingListManager;
        $this->currentShoppingListManager = $currentShoppingListManager;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Migrate a guest-created shopping list to customer user
     */
    public function migrateGuestShoppingList(
        CustomerVisitor $visitor,
        CustomerUser $customerUser,
        ShoppingList $shoppingList
    ) {
        if ($this->shoppingListLimitManager->isCreateEnabled()) {
            $this->moveShoppingListToCustomerUser($visitor, $customerUser, $shoppingList);
        } else {
            $this->mergeShoppingListWithCurrent($shoppingList);
        }
    }

    public function migrateGuestShoppingListWithOperationCode(ShoppingList $shoppingList)
    {
        return $this->shoppingListLimitManager->isCreateEnabled()
            ? $this->mergeShoppingListWithNewShoppingList($shoppingList)
            : $this->mergeShoppingListWithCurrent($shoppingList);
    }

    /**
     * Move a shopping list from customer visitor to customer user and make new list current
     */
    public function moveShoppingListToCustomerUser(
        CustomerVisitor $visitor,
        CustomerUser $customerUser,
        ShoppingList $shoppingList
    ) {
        if ($customerUser === $shoppingList->getCustomerUser()) {
            return self::OPERATION_NONE;
        }

        $visitor->removeShoppingList($shoppingList);
        $this->doctrineHelper->getEntityManagerForClass(CustomerVisitor::class)->flush();
        $lineItems = clone $shoppingList->getLineItems();
        $shoppingList->setCustomerUser($customerUser);
        foreach ($lineItems as $lineItem) {
            $lineItem->setCustomerUser($customerUser);
        }
        $this->currentShoppingListManager->setCurrent($customerUser, $shoppingList);
        $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class)->flush();

        $this->dispatchShoppingListPostTransfer($shoppingList, $shoppingList);

        return self::OPERATION_MOVE;
    }

    /**
     * Merge a visitor shopping list with a default customer user shopping list
     */
    public function mergeShoppingListWithCurrent(ShoppingList $shoppingList)
    {
        $currentShoppingList = $this->currentShoppingListManager->getCurrent();

        return $this->mergeShoppingListWithShoppingList($shoppingList, $currentShoppingList);
    }

    /**
     * Merge a visitor shopping list with a new default customer user shopping list.
     */
    public function mergeShoppingListWithNewShoppingList(ShoppingList $shoppingList): int
    {
        $currentShoppingList = $this->shoppingListManager->create(true, $shoppingList->getLabel());

        return $this->mergeShoppingListWithShoppingList($shoppingList, $currentShoppingList)
            ? self::OPERATION_MOVE
            : self::OPERATION_NONE;
    }

    private function mergeShoppingListWithShoppingList(
        ShoppingList $shoppingList,
        ShoppingList $currentShoppingList
    ): int {
        $notes = trim(implode(' ', [$currentShoppingList->getNotes(), $shoppingList->getNotes()]));
        $currentShoppingList->setNotes($notes);
        $lineItems = clone $shoppingList->getLineItems();
        if (count($lineItems) === 0) {
            return self::OPERATION_NONE;
        }

        $this->shoppingListManager->bulkAddLineItems(
            $lineItems->toArray(),
            $currentShoppingList,
            self::FLUSH_BATCH_SIZE
        );

        $this->dispatchShoppingListPostTransfer($shoppingList, $currentShoppingList);

        return self::OPERATION_MERGE;
    }

    private function dispatchShoppingListPostTransfer(
        ShoppingList $shoppingList,
        ShoppingList $currentShoppingList
    ): void {
        if (!$this->eventDispatcher->hasListeners(ShoppingListEventPostTransfer::NAME)) {
            return;
        }

        $event = new ShoppingListEventPostTransfer($shoppingList, $currentShoppingList);
        $this->eventDispatcher->dispatch($event, ShoppingListEventPostTransfer::NAME);
    }
}
