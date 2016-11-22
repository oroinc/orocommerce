<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductImageTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    const MAIN_TYPE = 'main';

    /**
     * @var ProductImageType
     */
    protected $productImageType;

    public function setUp()
    {
        $this->productImageType = new ProductImageType(self::MAIN_TYPE);
    }

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['productImage', new ProductImage()],
        ];

        $this->assertPropertyAccessors($this->productImageType, $properties);
    }

    public function testGetType()
    {
        $this->assertEquals(self::MAIN_TYPE, $this->productImageType->getType());
    }
}
