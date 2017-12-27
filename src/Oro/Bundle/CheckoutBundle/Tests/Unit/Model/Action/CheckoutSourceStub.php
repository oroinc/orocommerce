<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutSourceStub extends CheckoutSource
{
    /** @var ShoppingList */
    protected $shoppingList;

    /**
     * @return ShoppingList
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @param ShoppingList $shoppingList
     *
     * @return CheckoutSourceStub
     */
    public function setShoppingList($shoppingList)
    {
        $this->shoppingList = $shoppingList;

        return $this;
    }
}
