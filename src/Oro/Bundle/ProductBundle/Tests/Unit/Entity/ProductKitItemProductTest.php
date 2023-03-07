<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductKitItemProductTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['kitItem', new ProductKitItem()],
            ['product', new Product()],
            ['sortOrder', 42],
        ];

        self::assertPropertyAccessors(new ProductKitItemProduct(), $properties);
    }
}
