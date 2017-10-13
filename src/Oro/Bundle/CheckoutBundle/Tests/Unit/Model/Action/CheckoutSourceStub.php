<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutSourceStub extends CheckoutSource
{
    /** @var ShoppingList */
    protected $shoppingList;

    /** @var QuoteDemand */
    protected $quoteDemand;

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

    /**
     * @return QuoteDemand
     */
    public function getQuoteDemand()
    {
        return $this->quoteDemand;
    }

    /**
     * @param QuoteDemand $quoteDemand
     *
     * @return CheckoutSourceStub
     */
    public function setQuoteDemand($quoteDemand)
    {
        $this->quoteDemand = $quoteDemand;

        return $this;
    }
}
