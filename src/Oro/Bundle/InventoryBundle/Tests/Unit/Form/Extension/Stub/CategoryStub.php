<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryStub extends Category
{
    use InventoryFallbackTrait;
}
