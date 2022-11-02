<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Handles logic related to migrating shopping lists from customer visitors to customer users.
 */
class GuestShoppingListMigrationManager
{
    const FLUSH_BATCH_SIZE = 100;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ShoppingListLimitManager */
    private $shoppingListLimitManager;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

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

    /**
     * Migrate guest-created shopping list to customer user
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

    /**
     * Move shopping list from customer visitor to customer user and make new list current
     */
    public function moveShoppingListToCustomerUser(
        CustomerVisitor $visitor,
        CustomerUser $customerUser,
        ShoppingList $shoppingList
    ) {
        if ($customerUser == $shoppingList->getCustomerUser()) {
            return;
        }
        $visitor->removeShoppingList($shoppingList);
        $this->doctrineHelper->getEntityManagerForClass(CustomerVisitor::class)->flush();
        $lineItems = clone $shoppingList->getLineItems();
        foreach ($lineItems as $lineItem) {
            $lineItem->setCustomerUser($customerUser);
        }
        $shoppingList->setCustomerUser($customerUser);
        $this->currentShoppingListManager->setCurrent($customerUser, $shoppingList);
        $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class)->flush();
    }

    /**
     * Merge visitor shopping list with default customer user shopping list
     */
    public function mergeShoppingListWithCurrent(ShoppingList $shoppingList)
    {
        $customerUserShoppingList = $this->currentShoppingListManager->getCurrent();
        $lineItems = clone $shoppingList->getLineItems();

        $em = $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class);
        $em->remove($shoppingList);
        if (count($lineItems) === 0) {
            $em->flush();

            return;
        }

        $this->shoppingListManager->bulkAddLineItems(
            $lineItems->toArray(),
            $customerUserShoppingList,
            self::FLUSH_BATCH_SIZE
        );
    }
}
