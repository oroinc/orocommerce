<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
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
    private UserCurrencyManager $userCurrencyManager;

    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        TokenAccessorInterface $tokenAccessor,
        ShoppingListLimitManager $shoppingListLimitManager,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->shoppingListLimitManager = $shoppingListLimitManager;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    public function prePersist(ShoppingList $shoppingList): void
    {
        if (
            $this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
            && null === $shoppingList->getOwner()
        ) {
            $shoppingList->setOwner(
                $this->defaultUserProvider->getDefaultUser('oro_shopping_list.default_guest_shopping_list_owner')
            );
        }

        if (!$shoppingList->getCurrency()) {
            $shoppingList->setCurrency($this->userCurrencyManager->getUserCurrency());
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
