<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;

class ProductVariantLinkTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['parentProduct', new Product()],
            ['product', new Product()],
            ['visible', false, true],
        ];

        $this->assertPropertyAccessors(new ProductVariantLink(), $properties);
    }
}
