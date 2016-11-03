<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'xxx';
    }
}
