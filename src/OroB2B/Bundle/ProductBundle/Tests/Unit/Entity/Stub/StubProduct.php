<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

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
    public $visibility = [];

    /**
     * @var array
     */
    public $image = [];

    /**
     * @return AbstractEnumValue
     */
    public function getVisibility()
    {

    }
}
