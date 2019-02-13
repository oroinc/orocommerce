<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Twig\ProductExtension;
use Oro\Bundle\ProductBundle\Twig\ProductImageExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductImageExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePlaceholderProvider;

    /** @var ProductExtension */
    private $extension;

    protected function setUp()
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_product.provider.product_image_placeholder', $this->imagePlaceholderProvider)
            ->getContainer($this);

        $this->extension = new ProductImageExtension($container);
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

    /**
     * @return array
     */
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

    public function testGetProductImagePlaceholder()
    {
        $filter = 'product_large';
        $path = '/some/test/path.npg';

        $this->imagePlaceholderProvider->expects($this->once())
            ->method('getPath')
            ->with($filter)
            ->willReturn($path);

        $this->assertEquals(
            $path,
            self::callTwigFunction($this->extension, 'product_image_placeholder', [$filter])
        );
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
