<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListStub extends ShoppingList
{
    /**
     * @param CustomerVisitor $customerVisitor
     *
     * @return ShoppingListStub
     */
    public function addVisitor($customerVisitor)
    {
        if (!$this->visitors->contains($customerVisitor)) {
            $this->visitors->add($customerVisitor);
        }

        return $this;
    }
}
