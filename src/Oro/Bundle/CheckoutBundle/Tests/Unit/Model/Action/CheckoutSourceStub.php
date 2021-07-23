<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutSourceStub extends CheckoutSource
{
    /** @var ShoppingList */
    protected $shoppingList;

    /**
     * @param int $id
     * @return CheckoutSource
     */
    public function setId($id): CheckoutSource
    {
        $this->id = $id;

        return $this;
    }

    public function getShoppingList(): ?ShoppingList
    {
        return $this->shoppingList;
    }

    public function setShoppingList(ShoppingList $shoppingList): CheckoutSourceStub
    {
        $this->shoppingList = $shoppingList;

        return $this;
    }
}
