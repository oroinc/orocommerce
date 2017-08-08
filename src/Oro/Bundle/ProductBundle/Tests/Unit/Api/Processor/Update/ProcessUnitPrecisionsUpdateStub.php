<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Update;

use Oro\Bundle\ProductBundle\Api\Processor\Update\ProcessUnitPrecisionsUpdate;

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
