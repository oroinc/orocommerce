<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Create;


use Oro\Bundle\ProductBundle\Processor\Create\ProcessUnitPrecisionsCreate;

class ProcessUnitPrecisionsCreateStub extends ProcessUnitPrecisionsCreate
{
    public function setContext($context)
    {
        $this->context = $context;
    }

}
