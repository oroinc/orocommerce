<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Storage;

use OroB2B\Bundle\ProductBundle\Tests\Unit\Storage\AbstractSessionDataStorageTest;
use OroB2B\Bundle\RFPBundle\Storage\OffersDataStorage;

class OffersDataStorageTest extends AbstractSessionDataStorageTest
{
    protected function initStorage()
    {
        $this->storage = new OffersDataStorage($this->session);
    }
}
