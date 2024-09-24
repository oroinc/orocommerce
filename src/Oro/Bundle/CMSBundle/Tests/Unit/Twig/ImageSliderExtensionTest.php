<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\FileProxyStub;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide;
use Oro\Bundle\CMSBundle\Twig\ImageSliderExtension;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ImageSliderExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER = 'placeholder/image.png';

    private AttachmentManager|MockObject $attachmentManager;
    private ManagerRegistry|MockObject $doctrine;
    private ImageSliderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->attachmentManager->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(static function (File $file, string $filter, string $format) {
                return '/' . $filter . '/' . $file->getFilename() . ($format ? '.' . $format : '');
            });

        $imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $imagePlaceholderProvider->expects(self::any())
            ->method('getPath')
            ->with('original', self::anything())
            ->willReturn(self::PLACEHOLDER);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $container = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->add('oro_cms.provider.image_slider_image_placeholder.default', $imagePlaceholderProvider)
            ->add(PropertyAccessorInterface::class, $propertyAccessor)
            ->add(ManagerRegistry::class, $this->doctrine)
            ->getContainer($this);

        $this->extension = new ImageSliderExtension($container);
    }

    private function getImageFile(int $id, string $fileName, string $mimeType): File
    {
        $image = new File();
        ReflectionUtil::setId($image, $id);
        $image->setFilename($fileName);
        $image->setMimeType($mimeType);
        $image->setExtension(substr($fileName, strpos($fileName, '.') + 1));

        return $image;
    }

    private function getImageFileProxy(int $id, string $fileName, string $mimeType, bool $initialized): File
    {
        $image = new FileProxyStub();
        ReflectionUtil::setId($image, $id);
        $image->setFilename($fileName);
        $image->setMimeType($mimeType);
        $image->setExtension(substr($fileName, strpos($fileName, '.') + 1));
        if ($initialized) {
            $image->setInitialized(true);
        }

        return $image;
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
        $this->attachmentManager->expects(self::any())
            ->method('isWebpEnabledIfSupported')
            ->willReturn($isWebpEnabledIfSupported);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

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
        $extraLargeImage = $this->getImageFile(1, 'el-image.png', 'image/png');
        $largeImage = $this->getImageFile(2, 'large-image.png', 'image/png');
        $largeImageWebp = $this->getImageFile(3, 'large-image.webp', 'image/webp');
        $mediumImage = $this->getImageFile(4, 'medium-image.png', 'image/png');
        $mediumImageWebp = $this->getImageFile(5, 'medium-image.webp', 'image/webp');
        $smallImage = $this->getImageFile(6, 'small-image.png', 'image/png');
        $smallImageWebp = $this->getImageFile(7, 'small-image.webp', 'image/webp');

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

    public function testGetImageSlideSourcesWhenSomeImagesAreNotLoadedYet(): void
    {
        $extraLargeImage = $this->getImageFileProxy(1, 'el-image.png', 'image/png', false);
        $extraLargeImage2x = $this->getImageFileProxy(2, 'el-image-2x.png', 'image/png', true);
        $extraLargeImage3x = $this->getImageFileProxy(3, 'el-image-3x.png', 'image/png', false);
        $imageSlide = (new ImageSlide())
            ->setExtraLargeImage($extraLargeImage)
            ->setExtraLargeImage2x($extraLargeImage2x)
            ->setExtraLargeImage3x($extraLargeImage3x);

        $this->attachmentManager->expects(self::any())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['id' => [1, 3]])
            ->willReturn([$extraLargeImage, $extraLargeImage3x]);

        self::assertEquals(
            [
                [
                    'srcset' => '/original/el-image.png, /original/el-image-2x.png 2x, /original/el-image-3x.png 3x',
                    'type' => 'image/png'
                ]
            ],
            self::callTwigFunction($this->extension, 'oro_cms_image_slide_sources', [$imageSlide, []])
        );
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

    public function imageSlideDataProvider(): array
    {
        $extraLargeImage = $this->getImageFile(1, 'el-image.png', 'image/png');
        $extraLargeImage2x = $this->getImageFile(2, 'el-image-2x.png', 'image/png');
        $extraLargeImage3x = $this->getImageFile(3, 'el-image-3x.png', 'image/png');

        return [
            'default placeholder' => [
                'expected' => 'placeholder/image.png',
                'imageSlide' => new ImageSlide(),
            ],
            'default behavior' => [
                'expected' => '/original/el-image.png',
                'imageSlide' => (new ImageSlide())
                    ->setExtraLargeImage($extraLargeImage)
                    ->setExtraLargeImage2x($extraLargeImage2x)
                    ->setExtraLargeImage3x($extraLargeImage3x),
            ],
            'default behavior with custom format' => [
                'expected' => '/original/el-image.png.webp',
                'imageSlide' => (new ImageSlide())
                    ->setExtraLargeImage($extraLargeImage)
                    ->setExtraLargeImage2x($extraLargeImage2x)
                    ->setExtraLargeImage3x($extraLargeImage3x),
                'format' => 'webp',
            ],
            'fallback to 3x' => [
                'expected' => '/slider_extra_large/el-image-3x.png',
                'imageSlide' => (new ImageSlide())
                    ->setExtraLargeImage2x($extraLargeImage2x)
                    ->setExtraLargeImage3x($extraLargeImage3x),
            ],
            'fallback to 2x' => [
                'expected' => '/slider_extra_large/el-image-2x.png',
                'imageSlide' => (new ImageSlide())
                    ->setExtraLargeImage2x($extraLargeImage2x),
            ],
            'fallback to 2x with custom format' => [
                'expected' => '/slider_extra_large/el-image-2x.png.webp',
                'imageSlide' => (new ImageSlide())
                    ->setExtraLargeImage2x($extraLargeImage2x),
                'format' => 'webp',
            ]
        ];
    }
}
