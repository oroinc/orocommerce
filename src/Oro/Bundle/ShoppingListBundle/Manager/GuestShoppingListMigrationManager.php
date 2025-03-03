<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPostMergeEvent;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPostMoveEvent;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPreMergeEvent;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPreMoveEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles logic related to migrating shopping lists from customer visitors to customer users.
 */
class GuestShoppingListMigrationManager
{
    public const int FLUSH_BATCH_SIZE = 100;

    public const int OPERATION_NONE  = 0;
    public const int OPERATION_MOVE  = 1;
    public const int OPERATION_MERGE = 2;

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private ShoppingListLimitManager $shoppingListLimitManager,
        private ShoppingListManager $shoppingListManager,
        private CurrentShoppingListManager $currentShoppingListManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Migrate guest-created shopping list to customer user
     */
    public function migrateGuestShoppingList(
        CustomerVisitor $visitor,
        CustomerUser $customerUser,
        ShoppingList $shoppingList
    ): int {
        if ($this->shoppingListLimitManager->isCreateEnabled()) {
            return $this->moveShoppingListToCustomerUser($visitor, $customerUser, $shoppingList);
        }

        return $this->mergeShoppingListWithCurrent($shoppingList);
    }

    /**
     * Move shopping list from customer visitor to customer user and make new list current
     */
    public function moveShoppingListToCustomerUser(
        CustomerVisitor $visitor,
        CustomerUser $customerUser,
        ShoppingList $shoppingList
    ): int {
        if ($customerUser->getId() === $shoppingList->getCustomerUser()?->getId()) {
            return self::OPERATION_NONE;
        }

        $this->dispatchShoppingListPreMoveEvent($visitor, $customerUser, $shoppingList);

        $visitor->removeShoppingList($shoppingList);
        $this->doctrineHelper->getEntityManagerForClass(CustomerVisitor::class)->flush();

        $lineItems = clone $shoppingList->getLineItems();
        foreach ($lineItems as $lineItem) {
            $lineItem->setCustomerUser($customerUser);
        }
        foreach ($shoppingList->getTotals() as $shoppingListTotal) {
            $shoppingListTotal->setCustomerUser($customerUser);
        }
        $shoppingList->setCustomerUser($customerUser);
        $this->currentShoppingListManager->setCurrent($customerUser, $shoppingList);
        $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class)->flush();

        $this->dispatchShoppingListPostMoveEvent($visitor, $customerUser, $shoppingList);

        return self::OPERATION_MOVE;
    }

    /**
     * Merge visitor shopping list with default customer user shopping list
     */
    public function mergeShoppingListWithCurrent(ShoppingList $shoppingList): int
    {
        $customerUserShoppingList = $this->currentShoppingListManager->getCurrent();

        $this->dispatchShoppingListPreMergeEvent($customerUserShoppingList, $shoppingList);

        $lineItems = clone $shoppingList->getLineItems();

        $em = $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class);
        $em->remove($shoppingList);
        if (count($lineItems) === 0) {
            $em->flush();

            return self::OPERATION_NONE;
        }

        $this->shoppingListManager->bulkAddLineItems(
            $lineItems->toArray(),
            $customerUserShoppingList,
            self::FLUSH_BATCH_SIZE
        );

        $this->dispatchShoppingListPostMergeEvent($customerUserShoppingList, $shoppingList);

        return self::OPERATION_MERGE;
    }

    private function dispatchShoppingListPreMoveEvent(
        CustomerVisitor $visitor,
        CustomerUser $customerUser,
        ShoppingList $shoppingList
    ): void {
        if (!$this->eventDispatcher->hasListeners(ShoppingListPreMoveEvent::NAME)) {
            return;
        }

        $event = new ShoppingListPreMoveEvent($visitor, $customerUser, $shoppingList);
        $this->eventDispatcher->dispatch($event, ShoppingListPreMoveEvent::NAME);
    }

    private function dispatchShoppingListPostMoveEvent(
        CustomerVisitor $visitor,
        CustomerUser $customerUser,
        ShoppingList $shoppingList
    ): void {
        if (!$this->eventDispatcher->hasListeners(ShoppingListPostMoveEvent::NAME)) {
            return;
        }

        $event = new ShoppingListPostMoveEvent($visitor, $customerUser, $shoppingList);
        $this->eventDispatcher->dispatch($event, ShoppingListPostMoveEvent::NAME);
    }

    private function dispatchShoppingListPreMergeEvent(
        ShoppingList $currentShoppingList,
        ShoppingList $shoppingList
    ): void {
        if (!$this->eventDispatcher->hasListeners(ShoppingListPreMergeEvent::NAME)) {
            return;
        }

        $event = new ShoppingListPreMergeEvent($currentShoppingList, $shoppingList);
        $this->eventDispatcher->dispatch($event, ShoppingListPreMergeEvent::NAME);
    }

    private function dispatchShoppingListPostMergeEvent(
        ShoppingList $currentShoppingList,
        ShoppingList $shoppingList
    ): void {
        if (!$this->eventDispatcher->hasListeners(ShoppingListPostMergeEvent::NAME)) {
            return;
        }

        $event = new ShoppingListPostMergeEvent($currentShoppingList, $shoppingList);
        $this->eventDispatcher->dispatch($event, ShoppingListPostMergeEvent::NAME);
    }
}
