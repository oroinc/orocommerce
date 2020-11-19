<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Stub;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

class CheckoutLineItemStub extends CheckoutLineItem
{
    /**
     * @param int|null $id
     * @return self
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
