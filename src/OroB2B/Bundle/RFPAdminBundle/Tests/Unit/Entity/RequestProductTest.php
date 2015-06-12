<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPAdminBundle\Entity\Request;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;

class RequestProductTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['request', new Request()],
            ['product', new Product()],
        ];

        $this->assertPropertyAccessors(new RequestProduct(), $properties);
    }
}
