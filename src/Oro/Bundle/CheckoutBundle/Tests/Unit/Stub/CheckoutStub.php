<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Stub;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutStub extends Checkout
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
