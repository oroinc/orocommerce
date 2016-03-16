<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;

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
