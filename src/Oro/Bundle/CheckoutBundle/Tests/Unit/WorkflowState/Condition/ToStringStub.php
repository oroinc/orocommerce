<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Condition;

class ToStringStub
{
    /** @return string */
    #[\Override]
    public function __toString()
    {
        return 'string';
    }
}
