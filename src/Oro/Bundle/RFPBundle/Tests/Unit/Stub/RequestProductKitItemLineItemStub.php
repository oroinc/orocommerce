<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Stub;

use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;

class RequestProductKitItemLineItemStub extends RequestProductKitItemLineItem
{
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }
}
