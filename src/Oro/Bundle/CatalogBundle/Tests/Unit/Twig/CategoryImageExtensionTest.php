<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\CatalogBundle\Twig\CategoryImageExtension;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class CategoryImageExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER = 'placeholder/image.png';

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private PictureSourcesProviderInterface|\PHPUnit\Framework\MockObject\MockObject
        $pictureSourcesProvider;

    private CategoryImageExtension $extension;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);
        $imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->add(PictureSourcesProvider::class, $this->pictureSourcesProvider)
            ->add('oro_catalog.provider.category_image_placeholder', $imagePlaceholderProvider)
            ->getContainer($this);

        $this->extension = new CategoryImageExtension($container);

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

    public function testGetCategoryFilteredImage(): void
    {
        $file = new File();
        $file->setFilename('image.png');

        self::assertEquals(
            '/category_medium/image.png',
            self::callTwigFunction($this->extension, 'category_filtered_image', [$file, 'category_medium'])
        );
    }

    public function testGetCategoryFilteredImageWithFormat(): void
    {
        $file = new File();
        $file->setFilename('image.png');

        self::assertEquals(
            '/category_medium/image.png.webp',
            self::callTwigFunction($this->extension, 'category_filtered_image', [$file, 'category_medium', 'webp'])
        );
    }

    public function testGetCategoryFilteredImageWithoutFile(): void
    {
        self::assertEquals(
            '/category_medium/placeholder/image.png',
            self::callTwigFunction($this->extension, 'category_filtered_image', [null, 'category_medium'])
        );
    }

    public function testGetCategoryFilteredImageWithoutFileWithFormat(): void
    {
        self::assertEquals(
            '/category_medium/placeholder/image.png.webp',
            self::callTwigFunction($this->extension, 'category_filtered_image', [null, 'category_medium', 'webp'])
        );
    }

    public function testGetCategoryImagePlaceholder(): void
    {
        self::assertEquals(
            '/category_medium/placeholder/image.png',
            self::callTwigFunction($this->extension, 'category_image_placeholder', ['category_medium'])
        );
    }

    public function testGetCategoryImagePlaceholderWithFormat(): void
    {
        self::assertEquals(
            '/category_medium/placeholder/image.png.webp',
            self::callTwigFunction($this->extension, 'category_image_placeholder', ['category_medium', 'webp'])
        );
    }

    /**
     * @dataProvider getCategoryFilteredPictureSourcesReturnsPlaceholderSourcesWhenFileIsNullDataProvider
     *
     * @param bool $isWebpEnabledIfSupported
     * @param array $expected
     */
    public function testGetCategoryFilteredPictureSourcesReturnsPlaceholderSourcesWhenFileIsNull(
        bool $isWebpEnabledIfSupported,
        array $expected
    ): void {
        $this->attachmentManager
            ->expects(self::any())
            ->method('isWebpEnabledIfSupported')
            ->willReturn($isWebpEnabledIfSupported);

        $result = self::callTwigFunction(
            $this->extension,
            'category_filtered_picture_sources',
            [null]
        );

        self::assertEquals($expected, $result);
    }

    public function getCategoryFilteredPictureSourcesReturnsPlaceholderSourcesWhenFileIsNullDataProvider(): array
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
     * @dataProvider getCategoryFilteredPictureSourcesDataProvider
     */
    public function testGetCategoryFilteredPictureSources(array $attrs, array $expected): void
    {
        $file = new File();
        $filterName = 'sample_filter';

        $this->pictureSourcesProvider->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($file)
            ->willReturn($expected);

        $result = self::callTwigFunction(
            $this->extension,
            'category_filtered_picture_sources',
            [$file, $filterName, $attrs]
        );

        self::assertEquals($expected, $result);
    }

    public function getCategoryFilteredPictureSourcesDataProvider(): array
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
}
