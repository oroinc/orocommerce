<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductVariantLink;

class ProductVariantLinkTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['parentProduct', new Product()],
            ['product', new Product()],
            ['linked', false, true],
        ];

        $this->assertPropertyAccessors(new ProductVariantLink(), $properties);
    }
}
