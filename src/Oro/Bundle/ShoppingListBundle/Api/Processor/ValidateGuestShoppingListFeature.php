<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies access to shopping list API resources for customer visitors
 * if "Enable Guest Shopping List" feature is not enabled.
 */
class ValidateGuestShoppingListFeature implements ProcessorInterface
{
    private TokenStorageInterface $tokenStorage;
    private GuestShoppingListManager $shoppingListManager;

    public function __construct(TokenStorageInterface $tokenStorage, GuestShoppingListManager $shoppingListManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->shoppingListManager = $shoppingListManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken
            && !$this->shoppingListManager->isGuestShoppingListAvailable()
        ) {
            throw new AccessDeniedException('The access to guest shopping lists is denied.');
        }
    }
}
