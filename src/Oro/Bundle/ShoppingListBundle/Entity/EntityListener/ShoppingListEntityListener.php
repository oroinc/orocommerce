<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\EntityListener;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\Configuration;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class ShoppingListEntityListener
{
    /** @var DefaultUserProvider */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param DefaultUserProvider $defaultUserProvider
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(DefaultUserProvider $defaultUserProvider, TokenAccessorInterface $tokenAccessor)
    {
        $this->defaultUserProvider = $defaultUserProvider;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param ShoppingList $shoppingList
     */
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
    }
}
