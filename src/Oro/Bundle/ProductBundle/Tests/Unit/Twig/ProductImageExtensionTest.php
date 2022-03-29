<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Oro\Bundle\ProductBundle\Twig\ProductImageExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ProductImageExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER = 'placeholder/image.png';

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private PictureSourcesProviderInterface|\PHPUnit\Framework\MockObject\MockObject
        $pictureSourcesProvider;

    private ProductImageExtension $extension;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);
        $imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->add(PictureSourcesProvider::class, $this->pictureSourcesProvider)
            ->add('oro_product.provider.product_image_placeholder', $imagePlaceholderProvider)
            ->add('oro_product.helper.product_image_helper', new ProductImageHelper())
            ->getContainer($this);

        $this->extension = new ProductImageExtension($container);

        $this->attachmentManager
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(static function (File $file, string $filter, string $format) {
                return '/' . $filter . '/' . $file->getFilename() . ($format ? '.' . $format : '');
            });

        $imagePlaceholderProvider
            ->expects(self::any())
            ->method('getPath')
            ->willReturnCallback(static function (string $filter, string $format) {
                return '/' . $filter . '/' . self::PLACEHOLDER . ($format ? '.' . $format : '');
            });
    }

    /**
     * @dataProvider collectProductImageByTypesProvider
     */
    public function testCollectProductImagesByTypes(Product $product, array $imageTypes, array $expectedResult): void
    {
        $actualResult = self::callTwigFunction(
            $this->extension,
            'collect_product_images_by_types',
            [$product, $imageTypes]
        );

        self::assertEquals($expectedResult, array_values($actualResult));
    }

    public function collectProductImageByTypesProvider(): array
    {
        $product = new Product();
        $productImage1 = $this->createProductImage(1, ['additional']);
        $productImage2 = $this->createProductImage(2, ['additional']);
        $productImage3 = $this->createProductImage(3, ['additional', 'main']);
        $productImage4 = $this->createProductImage(4, ['listing']);

        $product
            ->addImage($productImage1)
            ->addImage($productImage2)
            ->addImage($productImage3)
            ->addImage($productImage4);

        return [
            'with images' => [
                'product' => $product,
                'imageTypes' => ['main', 'additional', 'listing'],
                'expectedResult' => [$productImage3, $productImage4, $productImage1, $productImage2],
            ],
            'duplicated images' => [
                'product' => (clone $product)->addImage($productImage1),
                'imageTypes' => ['main', 'additional', 'listing'],
                'expectedResult' => [$productImage3, $productImage4, $productImage1, $productImage2],
            ],
            'empty images' => [
                'product' => new Product(),
                'imageTypes' => ['main', 'additional'],
                'expectedResult' => [],
            ],
            'empty types' => [
                'product' => $product,
                'imageTypes' => [],
                'expectedResult' => [],
            ],
        ];
    }

    public function testGetProductFilteredImage(): void
    {
        $file = new File();
        $file->setFilename('image.png');

        self::assertEquals(
            '/product_small/image.png',
            self::callTwigFunction($this->extension, 'product_filtered_image', [$file, 'product_small'])
        );
    }

    public function testGetProductFilteredImageWithFormat(): void
    {
        $file = new File();
        $file->setFilename('image.png');

        self::assertEquals(
            '/product_small/image.png.webp',
            self::callTwigFunction($this->extension, 'product_filtered_image', [$file, 'product_small', 'webp'])
        );
    }

    public function testGetProductFilteredImageWithoutFile(): void
    {
        self::assertEquals(
            '/product_small/placeholder/image.png',
            self::callTwigFunction($this->extension, 'product_filtered_image', [null, 'product_small'])
        );
    }

    public function testGetProductFilteredImageWithoutFileWithFormat(): void
    {
        self::assertEquals(
            '/product_small/placeholder/image.png.webp',
            self::callTwigFunction($this->extension, 'product_filtered_image', [null, 'product_small', 'webp'])
        );
    }

    public function testGetProductImagePlaceholder(): void
    {
        self::assertEquals(
            '/product_large/placeholder/image.png',
            self::callTwigFunction($this->extension, 'product_image_placeholder', ['product_large'])
        );
    }

    public function testGetProductImagePlaceholderWithFormat(): void
    {
        self::assertEquals(
            '/product_large/placeholder/image.png.webp',
            self::callTwigFunction($this->extension, 'product_image_placeholder', ['product_large', 'webp'])
        );
    }

    /**
     * @dataProvider getProductFilteredPictureSourcesReturnsPlaceholderSourcesWhenFileIsNullDataProvider
     *
     * @param bool $isWebpEnabledIfSupported
     * @param array $expected
     */
    public function testGetProductFilteredPictureSourcesReturnsPlaceholderSourcesWhenFileIsNull(
        bool $isWebpEnabledIfSupported,
        array $expected
    ): void {
        $this->attachmentManager
            ->expects(self::any())
            ->method('isWebpEnabledIfSupported')
            ->willReturn($isWebpEnabledIfSupported);

        $result = self::callTwigFunction(
            $this->extension,
            'product_filtered_picture_sources',
            [null]
        );

        self::assertEquals($expected, $result);
    }

    public function getProductFilteredPictureSourcesReturnsPlaceholderSourcesWhenFileIsNullDataProvider(): array
    {
        return [
            'returns regular source when webp is not enabled is supported' => [
                'isWebpEnabledIfSupported' => false,
                'expected' => ['src' => '/original/placeholder/image.png', 'sources' => []],
            ],
            'returns regular and webp source when webp is enabled is supported' => [
                'isWebpEnabledIfSupported' => true,
                'expected' => [
                    'src' => '/original/placeholder/image.png',
                    'sources' => [
                        ['srcset' => '/original/placeholder/image.png.webp', 'type' => 'image/webp'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getProductFilteredPictureSourcesDataProvider
     */
    public function testGetProductFilteredPictureSources(array $attrs, array $expected): void
    {
        $file = new File();
        $filterName = 'sample_filter';

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($file)
            ->willReturn($expected);

        $result = self::callTwigFunction(
            $this->extension,
            'product_filtered_picture_sources',
            [$file, $filterName, $attrs]
        );

        self::assertEquals($expected, $result);
    }

    public function getProductFilteredPictureSourcesDataProvider(): array
    {
        return [
            'returns sources without webp if webp is not enabled if supported' => [
                'attrs' => ['sample_key' => 'sample_value'],
                'expected' => [
                    'src' => '/original/image.mime',
                    'sources' => [
                        [
                            'srcset' => '/original/image.mime.webp',
                            'type' => 'image/webp',
                            'sample_key' => 'sample_value',
                        ],
                    ],
                ],
            ],
            'attrs take precedence over srcset and type' => [
                'attrs' => ['srcset' => 'sample_value', 'type' => 'sample/type'],
                'expected' => [
                    'src' => '/original/image.mime',
                    'sources' => [
                        [
                            'srcset' => 'sample_value',
                            'type' => 'sample/type',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createProductImage(int $id, array $imageTypes = []): StubProductImage
    {
        $productImage = new StubProductImage();
        $productImage->setId($id);

        foreach ($imageTypes as $imageType) {
            $productImage->addType($imageType);
        }

        return $productImage;
    }
}
