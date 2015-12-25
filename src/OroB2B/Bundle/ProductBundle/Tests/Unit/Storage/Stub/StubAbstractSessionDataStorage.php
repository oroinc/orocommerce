<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Storage\Stub;

use OroB2B\Bundle\ProductBundle\Storage\AbstractSessionDataStorage;

class StubAbstractSessionDataStorage extends AbstractSessionDataStorage
{
    /** {@inheritdoc} */
    protected function getKey()
    {
        return 'key';
    }
}
