<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Provider\ProductImagesDimensionsProvider;

class ProductImagesDimensionsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $imageTypeProvider;

    /** @var ProductImagesDimensionsProvider */
    private $productImagesDimensionsProvider;

    /** @var ProductImage|\PHPUnit\Framework\MockObject\MockObject */
    private $productImage;

    /** @var ProductImageType|\PHPUnit\Framework\MockObject\MockObject */
    private $productImageType;

    /** @var ThemeImageType|\PHPUnit\Framework\MockObject\MockObject */
    private $themeImageType;

    protected function setUp(): void
    {
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->productImagesDimensionsProvider = new ProductImagesDimensionsProvider($this->imageTypeProvider);
        $this->productImage = $this->createMock(ProductImage::class);
        $this->productImageType = $this->createMock(ProductImageType::class);
        $this->themeImageType = $this->createMock(ThemeImageType::class);
    }

    public function testGetDimensionsForProductImage()
    {
        $this->themeImageType->expects(self::once())
            ->method('getDimensions')
            ->willReturn([ProductImageType::TYPE_MAIN]);

        $this->productImageType->expects(self::once())
            ->method('getType')
            ->willReturn(ProductImageType::TYPE_MAIN);

        $this->productImage->expects(self::once())
            ->method('getTypes')
            ->willReturn([$this->productImageType]);
        $this->imageTypeProvider->expects(self::once())
            ->method('getImageTypes')
            ->willReturn([ProductImageType::TYPE_MAIN => $this->themeImageType]);

        $result = $this->productImagesDimensionsProvider->getDimensionsForProductImage($this->productImage);

        $this->assertContains(ProductImageType::TYPE_MAIN, $result);
    }
}
