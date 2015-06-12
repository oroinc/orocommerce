<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class RequestProductItemTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['requestProduct', new RequestProduct()],
            ['productUnit', new ProductUnit()],
            ['quantity', 11],
            ['price', new Price()],
        ];

        $this->assertPropertyAccessors(new RequestProductItem(), $properties);
    }
}
