<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;

class ProductImageHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductImageHelper $productImageHelper
     */
    protected $productImageHelper;

    protected function setUp()
    {
        $this->productImageHelper = new ProductImageHelper();
    }

    public function testCountImagesByType()
    {
        $productImageType = new ProductImageType('main');

        $productImage = new ProductImage();
        $productImage->addType($productImageType);

        $collection = new ArrayCollection(
            [
                $productImage
            ]
        );

        $countValues = $this->productImageHelper->countImagesByType($collection);

        $this->assertEquals(
            [
                'main' => 1
            ],
            $countValues
        );
    }

    public function testEmpty()
    {
        $countValues = $this->productImageHelper->countImagesByType(new ArrayCollection());
        $this->assertEmpty($countValues);
    }
}
