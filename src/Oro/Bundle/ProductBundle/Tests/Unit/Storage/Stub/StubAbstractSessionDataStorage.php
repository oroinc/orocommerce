<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Storage\Stub;

use Oro\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;

class StubAbstractSessionDataStorage extends AbstractSessionDataStorage
{
    protected function getKey(): string
    {
        return 'key';
    }
}
