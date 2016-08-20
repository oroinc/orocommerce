<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Storage;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class ProductDataStorageTest extends AbstractSessionDataStorageTest
{
    protected function initStorage()
    {
        $this->storage = new ProductDataStorage($this->session);
    }
}
