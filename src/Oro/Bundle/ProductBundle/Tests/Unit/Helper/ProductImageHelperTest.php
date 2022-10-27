<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;

class ProductImageHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductImageHelper */
    protected $productImageHelper;

    protected function setUp(): void
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

    /**
     * @dataProvider sortProductImagesProvider
     */
    public function testSortImages(array $productImages, array $expectedResult)
    {
        $actualResult = $this->productImageHelper->sortImages($productImages);

        $this->assertEquals($expectedResult, array_values($actualResult));
    }

    public function sortProductImagesProvider(): array
    {
        $productImage1 = $this->createProductImage(1);
        $productImage2 = $this->createProductImage(2);
        $productImage3 = $this->createProductImage(3, ['additional']);
        $productImage4 = $this->createProductImage(4, ['main']);
        $productImage5 = $this->createProductImage(5, ['listing']);
        $productImage6 = $this->createProductImage(6, ['main', 'listing']);

        return [
            'without main, without listing' => [
                'productImages' => [$productImage2,$productImage1, $productImage3],
                'expectedResult' => [$productImage1, $productImage2, $productImage3]
            ],
            'with main, without listing' => [
                'productImages' => [$productImage2, $productImage3, $productImage1, $productImage4],
                'expectedResult' => [$productImage4, $productImage1, $productImage2, $productImage3]
            ],
            'without main, with listing' => [
                'productImages' => [$productImage2, $productImage3, $productImage1, $productImage5],
                'expectedResult' => [$productImage5, $productImage1, $productImage2, $productImage3]
            ],
            'with main, with listing' => [
                'productImages' => [$productImage2, $productImage3, $productImage1, $productImage5, $productImage4],
                'expectedResult' => [$productImage4, $productImage5, $productImage1, $productImage2, $productImage3]
            ],
            'main and listing is the same image' => [
                'productImages' => [$productImage2, $productImage3, $productImage6, $productImage1],
                'expectedResult' => [$productImage6, $productImage1, $productImage2, $productImage3]
            ],
            'no images' => [
                'productImages' => [],
                'expectedResult' => []
            ],
        ];
    }

    protected function createProductImage(int $id, array $imageTypes = []): StubProductImage
    {
        $productImage = new StubProductImage();
        $productImage->setId($id);

        foreach ($imageTypes as $imageType) {
            $productImage->addType($imageType);
        }

        return $productImage;
    }
}
