<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var ProductImage
     */
    protected $productImage;

    public function setUp()
    {
        $this->productImage = new ProductImage();
    }

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['types', ['main'], false]
        ];

        $this->assertPropertyAccessors($this->productImage, $properties);
    }

    public function testGetTypes()
    {
        $this->assertEquals([], $this->productImage->getTypes());
    }

    public function testHasType()
    {
        $this->productImage->setTypes(['main']);

        $this->assertTrue($this->productImage->hasType('main'));
        $this->assertFalse($this->productImage->hasType('someType'));
    }
}
