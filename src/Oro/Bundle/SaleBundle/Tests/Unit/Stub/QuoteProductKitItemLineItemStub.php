<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Stub;

use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;

class QuoteProductKitItemLineItemStub extends QuoteProductKitItemLineItem
{
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }
}
