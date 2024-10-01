<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Storage;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class ProductDataStorageTest extends AbstractSessionDataStorageTest
{
    #[\Override]
    protected function initStorage(): void
    {
        $this->storage = new ProductDataStorage($this->requestStack);
    }

    #[\Override]
    protected function getKey(): string
    {
        return 'oro_product_data';
    }
}
