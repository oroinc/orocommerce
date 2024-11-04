<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Condition;

class ToStringStub
{
    /** @return string */
    #[\Override]
    public function __toString()
    {
        return 'string';
    }
}
