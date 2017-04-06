<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;
use Oro\Bundle\ProductBundle\Twig\ProductImageExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductImageExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ProductExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new ProductImageExtension();
    }

    public function testGetName()
    {
        $this->assertEquals(ProductImageExtension::NAME, $this->extension->getName());
    }

    /**
     * @param Product $product
     * @param array $imageTypes
     * @param array $expectedResult
     *
     * @dataProvider collectProductImageByTypesProvider
     */
    public function testCollectProductImagesByTypes(Product $product, array $imageTypes, array $expectedResult)
    {
        $actualResult = self::callTwigFunction(
            $this->extension,
            'collect_product_images_by_types',
            [$product, $imageTypes]
        );

        $this->assertEquals($expectedResult, array_values($actualResult));
    }

    public function collectProductImageByTypesProvider()
    {
        $product = new Product();
        $productImage1 = $this->createProductImage(['additional']);
        $productImage2 = $this->createProductImage(['additional', 'main']);
        $productImage3 = $this->createProductImage(['listing']);

        $product
            ->addImage($productImage1)
            ->addImage($productImage2)
            ->addImage($productImage3);

        return [
            'with images' => [
                'product' => $product,
                'imageTypes' => ['main', 'additional'],
                'expectedResult' => [$productImage2, $productImage1]
            ],
            'empty images' => [
                'product' => new Product(),
                'imageTypes' => ['main', 'additional'],
                'expectedResult' => []
            ],
            'empty types' => [
                'product' => $product,
                'imageTypes' => [],
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @param array $imageTypes
     * @return ProductImage
     */
    protected function createProductImage(array $imageTypes = [])
    {
        $productImage = new ProductImage();

        foreach ($imageTypes as $imageType) {
            $productImage->addType($imageType);
        }

        return $productImage;
    }
}
