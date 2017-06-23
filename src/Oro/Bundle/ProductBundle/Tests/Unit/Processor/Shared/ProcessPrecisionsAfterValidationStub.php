<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessPrecisionsAfterValidation;

class ProcessPrecisionsAfterValidationStub extends ProcessPrecisionsAfterValidation
{
    public function handleProductUnitPrecisions(FormContext $formContext)
    {
        return true;
    }
}
