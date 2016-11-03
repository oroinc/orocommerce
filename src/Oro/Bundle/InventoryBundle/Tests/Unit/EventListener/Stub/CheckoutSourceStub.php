<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;

class CheckoutSourceStub extends CheckoutSource
{
    /**
     * @var mixed
     */
    protected $shoppingList;

    /**
     * @var mixed
     */
    protected $quoteDemand;

    /**
     * @return mixed
     */
    public function getQuoteDemand()
    {
        return $this->quoteDemand;
    }

    /**
     * @param mixed $quoteDemand
     *
     * @return $this
     */
    public function setQuoteDemand($quoteDemand)
    {
        $this->quoteDemand = $quoteDemand;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getShoppingList()
    {
        return $this->shoppingList;
    }

    /**
     * @param mixed $shoppingList
     *
     * @return $this
     */
    public function setShoppingList($shoppingList)
    {
        $this->shoppingList = $shoppingList;

        return $this;
    }
}
