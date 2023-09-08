<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\Configuration;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

/**
 * Set shopping list owner for anonymous user.
 * Reset internal state of ShoppingListLimitManager.
 */
class ShoppingListEntityListener
{
    /** @var DefaultUserProvider */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var ShoppingListLimitManager */
    private $shoppingListLimitManager;

    /** @var UserCurrencyManager */
    private $userCurrencyManager;

    public function __construct(
        DefaultUserProvider $defaultUserProvider,
        TokenAccessorInterface $tokenAccessor,
        ShoppingListLimitManager $shoppingListLimitManager
    ) {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->shoppingListLimitManager = $shoppingListLimitManager;
    }

    public function setUserCurrencyManager(UserCurrencyManager $userCurrencyManager): void
    {
        $this->userCurrencyManager = $userCurrencyManager;
    }

    public function prePersist(ShoppingList $shoppingList)
    {
        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
            && null === $shoppingList->getOwner()
        ) {
            $shoppingList->setOwner($this->defaultUserProvider->getDefaultUser(
                OroShoppingListExtension::ALIAS,
                Configuration::DEFAULT_GUEST_SHOPPING_LIST_OWNER
            ));
        }

        if (!$shoppingList->getCurrency()) {
            $shoppingList->setCurrency($this->userCurrencyManager->getUserCurrency());
        }
    }

    public function postPersist()
    {
        $this->shoppingListLimitManager->resetState();
    }

    public function postRemove()
    {
        $this->shoppingListLimitManager->resetState();
    }
}
