<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class that allows to set owner to shopping list.
 */
class ShoppingListOwnerManager
{
    public function __construct(
        private OwnerChecker $ownerChecker,
        private ManagerRegistry $doctrine
    ) {
    }

    public function setOwner(int $ownerId, ShoppingList $shoppingList): void
    {
        /** @var CustomerUser $user */
        $user = $this->doctrine->getRepository(CustomerUser::class)->find($ownerId);
        if (null === $user) {
            throw new \InvalidArgumentException(\sprintf('User with id=%s not exists', $ownerId));
        }
        if ($user === $shoppingList->getCustomerUser()) {
            return;
        }

        $currentOwner = $shoppingList->getOwner();
        $shoppingList->setCustomerUser($user);
        if ($this->ownerChecker->isOwnerCanBeSet($shoppingList)) {
            $this->assignLineItems($shoppingList, $user);
            $this->doctrine->getManagerForClass(ShoppingList::class)->flush();
        } else {
            // Revert owner to prevent possible unwanted owner change.
            $shoppingList->setOwner($currentOwner);

            throw new AccessDeniedException();
        }
    }

    private function assignLineItems(ShoppingList $shoppingList, CustomerUser $user): void
    {
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $lineItem->setCustomerUser($user);
        }
        foreach ($shoppingList->getSavedForLaterLineItems() as $savedForLaterLineItem) {
            $savedForLaterLineItem->setCustomerUser($user);
        }
    }
}
