<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Update;

use Oro\Bundle\ProductBundle\Processor\Update\ProcessUnitPrecisionsUpdate;

class ProcessUnitPrecisionsUpdateStub extends ProcessUnitPrecisionsUpdate
{
    /**
     * @param $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}
