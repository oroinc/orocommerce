<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub;

use Oro\Component\ChainProcessor\Context;

class ContextStub extends Context
{
    public function getConfig()
    {
        return null;
    }

    public function setConfig()
    {
        return null;
    }


    public function setMetadata()
    {
        return null;
    }

    public function getMetadata()
    {
        return null;
    }
}
