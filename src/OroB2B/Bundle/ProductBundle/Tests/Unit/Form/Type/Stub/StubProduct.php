<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class StubProduct extends Product
{
    /**
     * @var array
     */
    public $inventoryStatus = [];

    /**
     * @var array
     */
    public $image = [];
}
