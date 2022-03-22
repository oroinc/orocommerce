<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductKitItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');

        $properties = [
            ['id', 123],
            ['productUnit', new ProductUnit()],
            ['productKit', new Product()],
            ['sortOrder', 42],
            ['minimumQuantity', 42],
            ['maximumQuantity', 4242],
            ['optional', false, true],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        self::assertPropertyAccessors(new ProductKitItem(), $properties);
    }

    public function testCollections(): void
    {
        $collections = [
            ['labels', new ProductKitItemLabel()],
            ['products', new Product()],
            ['referencedUnitPrecisions', new ProductUnitPrecision()],
        ];

        self::assertPropertyCollections(new ProductKitItemStub(), $collections);
    }
}
