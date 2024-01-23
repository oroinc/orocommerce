<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide;
use Oro\Bundle\CMSBundle\Twig\ImageSliderExtension;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface as ImagePlaceholderProvider;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ImageSliderExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER = 'placeholder/image.png';

    private AttachmentManager|MockObject $attachmentManager;
    private ImagePlaceholderProvider|MockObject $imagePlaceholderProvider;
    private PropertyAccessor $propertyAccessor;
    private ImageSliderExtension $extension;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProvider::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $container = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->add(
                'oro_cms.provider.image_slider_image_placeholder.default',
                $this->imagePlaceholderProvider
            )
            ->add('property_accessor', $this->propertyAccessor)
            ->getContainer($this);

        $this->extension = new ImageSliderExtension($container);

        $this->attachmentManager
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(static function (File $file, string $filter, string $format) {
                return '/' . $filter . '/' . $file->getFilename() . ($format ? '.' . $format : '');
            });

        $this->imagePlaceholderProvider
            ->expects(self::any())
            ->method('getPath')
            ->with('original', self::anything())
            ->willReturn(self::PLACEHOLDER);
    }

    public function testGetImageSlideSourcesReturnsEmptyArrayIfImageSlideHasNoImages(): void
    {
        $imageSlide = new ImageSlide();

        self::assertEquals(
            [],
            self::callTwigFunction($this->extension, 'oro_cms_image_slide_sources', [$imageSlide, []])
        );
    }

    /**
     * @dataProvider getImageSlideSourcesDataProvider
     */
    public function testGetImageSlideSources(
        ImageSlide $imageSlide,
        array $imageVariantSettings,
        bool $isWebpEnabledIfSupported,
        array $expected
    ): void {
        $this->attachmentManager
            ->expects(self::any())
            ->method('isWebpEnabledIfSupported')
            ->willReturn($isWebpEnabledIfSupported);

        $result = self::callTwigFunction(
            $this->extension,
            'oro_cms_image_slide_sources',
            [$imageSlide, $imageVariantSettings]
        );
        self::assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getImageSlideSourcesDataProvider(): array
    {
        $extraLargeImage = (new File())
            ->setFilename('el-image.png')
            ->setMimeType('image/png')
            ->setExtension('png');
        $largeImage = (new File())
            ->setFilename('large-image.png')
            ->setMimeType('image/png')
            ->setExtension('png');
        $largeImageWebp = (new File())
            ->setFilename('large-image.webp')
            ->setMimeType('image/webp')
            ->setExtension('webp');
        $mediumImage = (new File())
            ->setFilename('medium-image.png')
            ->setMimeType('image/png')
            ->setExtension('png');
        $mediumImageWebp = (new File())
            ->setFilename('medium-image.webp')
            ->setMimeType('image/webp')
            ->setExtension('webp');
        $smallImage = (new File())
            ->setFilename('small-image.png')
            ->setMimeType('image/png')
            ->setExtension('png');
        $smallImageWebp = (new File())
            ->setFilename('small-image.webp')
            ->setMimeType('image/webp')
            ->setExtension('webp');

        return [
            'fallbacks to 3x image' => [
                'imageSlide' => (new ImageSlide())->setExtraLargeImage3x($extraLargeImage),
                'imageVariantSettings' => [],
                'isWebpEnabledIfSupported' => false,
                'expected' => [
                    [
                        'srcset' => '/original/el-image.png, /original/el-image.png 2x, /original/el-image.png 3x',
                        'type' => 'image/png',
                    ],
                ],
            ],
            'fallbacks to 3x image and applies fallback filters' => [
                'imageSlide' => (new ImageSlide())->setExtraLargeImage3x($extraLargeImage),
                'imageVariantSettings' => [
                    'extraLargeImage' => [
                        'fallback_filter' => 'f1x',
                        'fallback_filter2x' => 'f2x',
                    ],
                ],
                'isWebpEnabledIfSupported' => false,
                'expected' => [
                    [
                        'srcset' => '/f1x/el-image.png, /f2x/el-image.png 2x, /original/el-image.png 3x',
                        'type' => 'image/png',
                    ],
                ],
            ],
            'fallbacks to 2x image and applies fallback filters' => [
                'imageSlide' => (new ImageSlide())->setExtraLargeImage2x($extraLargeImage),
                'imageVariantSettings' => [
                    'extraLargeImage' => [
                        'fallback_filter' => 'f1x',
                        'fallback_filter2x' => 'f2x',
                    ],
                ],
                'isWebpEnabledIfSupported' => false,
                'expected' => [
                    [
                        'srcset' => '/f1x/el-image.png, /original/el-image.png 2x',
                        'type' => 'image/png',
                    ],
                ],
            ],
            'uses filter and adds media query' => [
                'imageSlide' => (new ImageSlide())
                    ->setLargeImage($largeImage)
                    ->setMediumImage($mediumImage)
                    ->setSmallImage($smallImage),
                'imageVariantSettings' => [
                    'largeImage' => [
                        'filter' => 'original_filter',
                        'media' => '(max-width:9999px)',
                    ],
                    'mediumImage' => [
                        'filter' => 'original_medium_filter',
                        'media' => '(max-width:456px)',
                    ],
                    'smallImage' => [
                        'filter' => 'original_small_filter',
                        'media' => '(max-width:123px)',
                    ],
                ],
                'isWebpEnabledIfSupported' => false,
                'expected' => [
                    [
                        'srcset' => '/original_small_filter/small-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:123px)',
                    ],
                    [
                        'srcset' => '/original_medium_filter/medium-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:456px)',
                    ],
                    [
                        'srcset' => '/original_filter/large-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:9999px)',
                    ],
                ],
            ],
            'adds webp image sources is webp is enabled if supported' => [
                'imageSlide' => (new ImageSlide())
                    ->setLargeImage($largeImage)
                    ->setMediumImage($mediumImage)
                    ->setSmallImage($smallImage),
                'imageVariantSettings' => [
                    'largeImage' => [
                        'filter' => 'original_filter',
                        'media' => '(max-width:9999px)',
                    ],
                    'mediumImage' => [
                        'filter' => 'original_medium_filter',
                        'media' => '(max-width:456px)',
                    ],
                    'smallImage' => [
                        'filter' => 'original_small_filter',
                        'media' => '(max-width:123px)',
                    ],
                ],
                'isWebpEnabledIfSupported' => true,
                'expected' => [
                    [
                        'srcset' => '/original_small_filter/small-image.png.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:123px)',
                    ],
                    [
                        'srcset' => '/original_small_filter/small-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:123px)',
                    ],
                    [
                        'srcset' => '/original_medium_filter/medium-image.png.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:456px)',
                    ],
                    [
                        'srcset' => '/original_medium_filter/medium-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:456px)',
                    ],
                    [
                        'srcset' => '/original_filter/large-image.png.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:9999px)',
                    ],
                    [
                        'srcset' => '/original_filter/large-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:9999px)',
                    ],
                ],
            ],
            'does not add webp image sources is webp is enabled if supported but images are already webp' => [
                'imageSlide' => (new ImageSlide())
                    ->setLargeImage($largeImageWebp)
                    ->setMediumImage($mediumImageWebp)
                    ->setSmallImage($smallImageWebp),
                'imageVariantSettings' => [
                    'largeImage' => [
                        'filter' => 'original_filter',
                        'media' => '(max-width:9999px)',
                    ],
                    'mediumImage' => [
                        'filter' => 'original_medium_filter',
                        'media' => '(max-width:456px)',
                    ],
                    'smallImage' => [
                        'filter' => 'original_small_filter',
                        'media' => '(max-width:123px)',
                    ],
                ],
                'isWebpEnabledIfSupported' => true,
                'expected' => [
                    [
                        'srcset' => '/original_small_filter/small-image.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:123px)',
                    ],
                    [
                        'srcset' => '/original_medium_filter/medium-image.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:456px)',
                    ],
                    [
                        'srcset' => '/original_filter/large-image.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:9999px)',
                    ],
                ],
            ],
            'type not added if different mimetypes for [standard, 2x and 3x] images' => [
                'imageSlide' => (new ImageSlide())
                    ->setLargeImage($largeImage)
                    ->setLargeImage2x($largeImageWebp)
                    ->setMediumImage($mediumImageWebp)
                    ->setMediumImage2x($mediumImageWebp)
                    ->setSmallImage($smallImage)
                    ->setSmallImage2x($smallImage),
                'imageVariantSettings' => [
                    'largeImage' => [
                        'filter' => 'original_filter',
                        'media' => '(max-width:9999px)',
                    ],
                    'mediumImage' => [
                        'filter' => 'original_medium_filter',
                        'media' => '(max-width:456px)',
                    ],
                    'smallImage' => [
                        'filter' => 'original_small_filter',
                        'media' => '(max-width:123px)',
                    ],
                ],
                'isWebpEnabledIfSupported' => true,
                'expected' => [
                    [
                        'srcset' => '/original_small_filter/small-image.png.webp, /original/small-image.png.webp 2x',
                        'type' => 'image/webp',
                        'media' => '(max-width:123px)',
                    ],
                    [
                        'srcset' => '/original_small_filter/small-image.png, /original/small-image.png 2x',
                        'type' => 'image/png',
                        'media' => '(max-width:123px)',
                    ],
                    [
                        'srcset' => '/original_medium_filter/medium-image.webp, /original/medium-image.webp 2x',
                        'type' => 'image/webp',
                        'media' => '(max-width:456px)',
                    ],
                    [
                        'srcset' => '/original_filter/large-image.png.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:9999px)',
                    ],
                    [
                        'srcset' => '/original_filter/large-image.png, /original/large-image.webp 2x',
                        'media' => '(max-width:9999px)',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider imageSlideDataProvider
     */
    public function testGetImageSlideImage(string $expected, ImageSlide $imageSlide, string $format = ''): void
    {
        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'oro_cms_image_slide_image', [$imageSlide, $format])
        );
    }

    public function imageSlideDataProvider(): \Generator
    {
        $extraLargeImage = (new File())
            ->setFilename('el-image.png')
            ->setMimeType('image/png')
            ->setExtension('png');
        $extraLargeImage2x = (new File())
            ->setFilename('el-image-2x.png')
            ->setMimeType('image/png')
            ->setExtension('png');
        $extraLargeImage3x = (new File())
            ->setFilename('el-image-3x.png')
            ->setMimeType('image/png')
            ->setExtension('png');

        yield 'default placeholder' => [
            'expected' => 'placeholder/image.png',
            'imageSlide' => new ImageSlide(),
        ];

        yield 'default behavior' => [
            'expected' => '/original/el-image.png',
            'imageSlide' => (new ImageSlide())
                ->setExtraLargeImage($extraLargeImage)
                ->setExtraLargeImage2x($extraLargeImage2x)
                ->setExtraLargeImage3x($extraLargeImage3x),
        ];

        yield 'default behavior with custom format' => [
            'expected' => '/original/el-image.png.webp',
            'imageSlide' => (new ImageSlide())
                ->setExtraLargeImage($extraLargeImage)
                ->setExtraLargeImage2x($extraLargeImage2x)
                ->setExtraLargeImage3x($extraLargeImage3x),
            'format' => 'webp',
        ];

        yield 'fallback to 3x' => [
            'expected' => '/slider_extra_large/el-image-3x.png',
            'imageSlide' => (new ImageSlide())
                ->setExtraLargeImage2x($extraLargeImage2x)
                ->setExtraLargeImage3x($extraLargeImage3x),
        ];

        yield 'fallback to 2x' => [
            'expected' => '/slider_extra_large/el-image-2x.png',
            'imageSlide' => (new ImageSlide())
                ->setExtraLargeImage2x($extraLargeImage2x),
        ];

        yield 'fallback to 2x with custom format' => [
            'expected' => '/slider_extra_large/el-image-2x.png.webp',
            'imageSlide' => (new ImageSlide())
                ->setExtraLargeImage2x($extraLargeImage2x),
            'format' => 'webp',
        ];
    }
}
