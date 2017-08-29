<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;

class ProductImagesDimensionsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ImageTypeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $imageTypeProvider;

    /**
     * @var ProductImagesDimensionsProvider
     */
    protected $productImagesDimensionsProvider;

    /**
     * @var ProductImage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productImage;

    /**
     * @var ProductImageType|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productImageType;

    /**
     * @var ThemeImageType|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeImageType;

    protected function setUp()
    {
        $this->imageTypeProvider = $this->getMockBuilder(ImageTypeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productImagesDimensionsProvider = new ProductImagesDimensionsProvider($this->imageTypeProvider);
        $this->productImage = $this->createMock(ProductImage::class);
        $this->productImageType = $this->createMock(ProductImageType::class);
        $this->themeImageType = $this->createMock(ThemeImageType::class);
    }

    public function testGetDimensionsForProductImage()
    {
        $this->themeImageType
            ->expects(static::once())
            ->method('getDimensions')
            ->willReturn(
                [
                    ProductImageType::TYPE_MAIN
                ]
            );

        $this->productImageType
            ->expects(static::once())
            ->method('getType')
            ->willReturn(ProductImageType::TYPE_MAIN);

        $this->productImage
            ->expects(static::once())
            ->method('getTypes')
            ->willReturn(
                [
                    $this->productImageType
                ]
            );
        $this->imageTypeProvider
            ->expects(static::once())
            ->method('getImageTypes')
            ->willReturn(
                [
                    ProductImageType::TYPE_MAIN => $this->themeImageType
                ]
            );

        $result = $this->productImagesDimensionsProvider->getDimensionsForProductImage($this->productImage);

        $this->assertContains(ProductImageType::TYPE_MAIN, $result);
    }
}
