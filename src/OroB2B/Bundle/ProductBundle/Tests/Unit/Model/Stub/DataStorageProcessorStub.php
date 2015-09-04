<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model\Stub;

use OroB2B\Bundle\ProductBundle\Model\AbstractDataStorageProcessor;

class DataStorageProcessorStub extends AbstractDataStorageProcessor
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'data_storage_processor';
    }
}
