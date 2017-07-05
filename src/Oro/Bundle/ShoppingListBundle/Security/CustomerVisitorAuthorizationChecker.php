<?php

namespace Oro\Bundle\ShoppingListBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * @internal
 */
class CustomerVisitorAuthorizationChecker implements AuthorizationCheckerInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorage                  $tokenStorage
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, TokenStorage $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Grant access to customer visitor own shopping lists and line items only.
     *
     * {@inheritDoc}
     */
    public function isGranted($attribute, $object = null)
    {
        $token = $this->tokenStorage->getToken();

        $user = $token->getUser();

        if ($user instanceof CustomerUser) { // if user is logged in just check acl
            return $this->authorizationChecker->isGranted($attribute, $object);
        }
        if ($token instanceof AnonymousCustomerUserToken) {
            $visitor = $token->getVisitor();

            if ($object instanceof ShoppingList && in_array($attribute, ['VIEW', 'EDIT'], true)) {
                return $visitor->getShoppingLists()->contains($object);
            }

            if ($object instanceof LineItem && in_array($attribute, ['VIEW', 'EDIT'], true)) {
                return $this->visitorShoppingListsContainsLineItem($visitor, $object);
            }
        }

        return false;
    }

    /**
     * @param CustomerVisitor $visitor
     * @param LineItem        $lineItem
     * @return bool
     */
    private function visitorShoppingListsContainsLineItem(CustomerVisitor $visitor, LineItem $lineItem)
    {
        foreach ($visitor->getShoppingLists() as $shoppingList) {
            if ($shoppingList->getLineItems()->contains($lineItem)) {
                return true;
            }
        }

        return false;
    }
}
