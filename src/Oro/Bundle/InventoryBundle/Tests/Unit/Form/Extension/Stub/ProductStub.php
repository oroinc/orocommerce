<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    use InventoryFallbackTrait;
}
