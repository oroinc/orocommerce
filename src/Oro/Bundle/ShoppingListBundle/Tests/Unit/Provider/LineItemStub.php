<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class LineItemStub extends LineItem
{
    /**
     * @param $id
     *
     * @return $this
     */
    public function setId($id): LineItemStub
    {
        $this->id = $id;

        return $this;
    }
}
