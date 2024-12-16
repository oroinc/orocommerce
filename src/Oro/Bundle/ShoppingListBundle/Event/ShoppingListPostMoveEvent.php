<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Event executed after assigning shopping list to Customer user in GuestShoppingListMigrationManager
 */
class ShoppingListPostMoveEvent extends ShoppingListEvent
{
    public const string NAME = 'oro_shopping_list.post_move';

    public function __construct(
        private CustomerVisitor $visitor,
        private CustomerUser $customerUser,
        ShoppingList $shoppingList
    ) {
        parent::__construct($shoppingList);
    }

    public function getVisitor(): CustomerVisitor
    {
        return $this->visitor;
    }

    public function setVisitor(CustomerVisitor $visitor): self
    {
        $this->visitor = $visitor;
        return $this;
    }

    public function getCustomerUser(): CustomerUser
    {
        return $this->customerUser;
    }

    public function setCustomerUser(CustomerUser $customerUser): self
    {
        $this->customerUser = $customerUser;
        return $this;
    }
}
