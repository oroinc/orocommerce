<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Condition;

class ToStringStub
{
    /** @return string */
    #[\Override]
    public function __toString()
    {
        return 'string';
    }
}
