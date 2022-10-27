<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

/**
 * Set shopping list owner for anonymous user.
 * Reset internal state of ShoppingListLimitManager.
 */
class ShoppingListEntityListener
{
    private DefaultUserProvider $defaultUserProvider;
    private TokenAccessorInterface $tokenAccessor;
    private ShoppingListLimitManager $shoppingListLimitManager;

    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        TokenAccessorInterface $tokenAccessor,
        ShoppingListLimitManager $shoppingListLimitManager
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->shoppingListLimitManager = $shoppingListLimitManager;
    }

    public function prePersist(ShoppingList $shoppingList): void
    {
        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
            && null === $shoppingList->getOwner()
        ) {
            $shoppingList->setOwner(
                $this->defaultUserProvider->getDefaultUser('oro_shopping_list.default_guest_shopping_list_owner')
            );
        }
    }

    public function postPersist(): void
    {
        $this->shoppingListLimitManager->resetState();
    }

    public function postRemove(): void
    {
        $this->shoppingListLimitManager->resetState();
    }
}
