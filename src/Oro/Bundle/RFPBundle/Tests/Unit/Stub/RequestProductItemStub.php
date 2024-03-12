<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Stub;

use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

class RequestProductItemStub extends RequestProductItem
{
    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }
}
