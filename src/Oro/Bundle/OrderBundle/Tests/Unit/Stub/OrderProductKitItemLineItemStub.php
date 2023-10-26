<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Stub;

use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;

class OrderProductKitItemLineItemStub extends OrderProductKitItemLineItem
{
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }
}
