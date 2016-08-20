<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;

class CheckoutSourceStub extends CheckoutSource
{
    protected $shoppingList;

    /**
     * @return mixed
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @param mixed $shoppingList
     * @return CheckoutSourceStub
     */
    public function setShoppingList($shoppingList)
    {
        $this->shoppingList = $shoppingList;

        return $this;
    }
}
