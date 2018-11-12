<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductVariantLinkTest extends \PHPUnit\Framework\TestCase
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
