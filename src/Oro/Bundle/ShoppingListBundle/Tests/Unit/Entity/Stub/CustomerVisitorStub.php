<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CustomerVisitorStub extends CustomerVisitor
{
    /** @var ArrayCollection */
    private $shoppingLists;

    /**
     * CustomerVisitorStub constructor.
     */
    public function __construct()
    {
        $this->shoppingLists = new ArrayCollection();
    }

    /**
     * @param ShoppingList $shoppingLists
     *
     * @return CustomerVisitorStub
     */
    public function addShoppingList($shoppingLists)
    {
        if (!$this->shoppingLists->contains($shoppingLists)) {
            $this->shoppingLists->add($shoppingLists);
        }

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getShoppingLists()
    {
        return $this->shoppingLists;
    }

    /**
     * @param ShoppingList $shoppingLists
     *
     * @return CustomerVisitorStub
     */
    public function removeShoppingList($shoppingLists)
    {
        if ($this->shoppingLists->contains($shoppingLists)) {
            $this->shoppingLists->removeElement($shoppingLists);
        }

        return $this;
    }
}
