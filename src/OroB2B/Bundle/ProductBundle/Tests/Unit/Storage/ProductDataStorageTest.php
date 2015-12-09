<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Storage;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class ProductDataStorageTest extends AbstractSessionDataStorageTest
{
    protected function initStorage()
    {
        $this->storage = new ProductDataStorage($this->session);
    }
}
