<?php

namespace Oro\Bundle\ShoppingListBundle\Api;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The factory to create the default shopping list.
 */
class DefaultShoppingListFactory
{
    private TokenStorageInterface $tokenStorage;
    private ShoppingListManager $shoppingListManager;
    private GuestShoppingListManager $guestShoppingListManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ShoppingListManager $shoppingListManager,
        GuestShoppingListManager $guestShoppingListManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->shoppingListManager = $shoppingListManager;
        $this->guestShoppingListManager = $guestShoppingListManager;
    }

    public function create(): ?ShoppingList
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $shoppingList = null;
        if ($token instanceof AnonymousCustomerUserToken) {
            if ($this->guestShoppingListManager->isGuestShoppingListAvailable()) {
                $shoppingList = $this->guestShoppingListManager->createShoppingListForCustomerVisitor();
            }
        } else {
            $user = $token->getUser();
            if ($user instanceof CustomerUser) {
                $shoppingList = $this->shoppingListManager->create(true);
            }
        }

        return $shoppingList;
    }
}
