<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductImageTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    private const MAIN_TYPE = 'main';

    private ProductImageType $productImageType;

    protected function setUp(): void
    {
        $this->productImageType = new ProductImageType(self::MAIN_TYPE);
    }

    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['productImage', new ProductImage()],
        ];

        $this->assertPropertyAccessors($this->productImageType, $properties);
    }

    public function testGetType()
    {
        $this->assertEquals(self::MAIN_TYPE, $this->productImageType->getType());
    }
}
