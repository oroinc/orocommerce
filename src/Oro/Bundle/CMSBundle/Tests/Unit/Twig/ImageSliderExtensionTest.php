<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide;
use Oro\Bundle\CMSBundle\Twig\ImageSliderExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ImageSliderExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const PLACEHOLDER = 'placeholder/image.png';

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private ImageSliderExtension $extension;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $container = self::getContainerBuilder()
            ->add(AttachmentManager::class, $this->attachmentManager)
            ->getContainer($this);

        $this->extension = new ImageSliderExtension($container);

        $this->attachmentManager
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(static function (File $file, string $filter, string $format) {
                return '/' . $filter . '/' . $file->getFilename() . ($format ? '.' . $format : '');
            });
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
     *
     * @param File $mainImage
     * @param File|null $mediumImage
     * @param File|null $smallImage
     * @param array $imageVariantSettings
     * @param array $expected
     */
    public function testGetImageSlideSources(
        File $mainImage,
        ?File $mediumImage,
        ?File $smallImage,
        array $imageVariantSettings,
        bool $isWebpEnabledIfSupported,
        array $expected
    ): void {
        $imageSlide = new ImageSlide();
        $imageSlide->setMainImage($mainImage);
        $imageSlide->setMediumImage($mediumImage);
        $imageSlide->setSmallImage($smallImage);

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
        $mainImage = (new File())
            ->setFilename('main-image.png')
            ->setMimeType('image/png')
            ->setExtension('png');
        $mainImageWebp = (new File())
            ->setFilename('main-image.webp')
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
            'fallbacks to main image' => [
                'mainImage' => $mainImage,
                'mediumImage' => null,
                'smallImage' => null,
                'imageVariantSettings' => [],
                'isWebpEnabledIfSupported' => false,
                'expected' => [
                    [
                        'srcset' => '/original/main-image.png',
                        'type' => 'image/png',
                    ],
                    [
                        'srcset' => '/original/main-image.png',
                        'type' => 'image/png',
                    ],
                    [
                        'srcset' => '/original/main-image.png',
                        'type' => 'image/png',
                    ],
                ],
            ],
            'fallbacks to main image and applies fallback filters' => [
                'mainImage' => $mainImage,
                'mediumImage' => null,
                'smallImage' => null,
                'imageVariantSettings' => [
                    'mainImage' => [
                        'filter' => 'original_filter',
                    ],
                    'mediumImage' => [
                        'fallback_filter' => 'medium_filter',
                    ],
                    'smallImage' => [
                        'fallback_filter' => 'medium_filter',
                    ],
                ],
                'isWebpEnabledIfSupported' => false,
                'expected' => [
                    [
                        'srcset' => '/medium_filter/main-image.png',
                        'type' => 'image/png',
                    ],
                    [
                        'srcset' => '/medium_filter/main-image.png',
                        'type' => 'image/png',
                    ],
                    [
                        'srcset' => '/original_filter/main-image.png',
                        'type' => 'image/png',
                    ],
                ],
            ],
            'uses filter and adds media query' => [
                'mainImage' => $mainImage,
                'mediumImage' => $mediumImage,
                'smallImage' => $smallImage,
                'imageVariantSettings' => [
                    'mainImage' => [
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
                        'srcset' => '/original_filter/main-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:9999px)',
                    ],
                ],
            ],
            'adds webp image sources is webp is enabled if supported' => [
                'mainImage' => $mainImage,
                'mediumImage' => $mediumImage,
                'smallImage' => $smallImage,
                'imageVariantSettings' => [
                    'mainImage' => [
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
                        'srcset' => '/original_filter/main-image.png.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:9999px)',
                    ],
                    [
                        'srcset' => '/original_filter/main-image.png',
                        'type' => 'image/png',
                        'media' => '(max-width:9999px)',
                    ],
                ],
            ],
            'does not add webp image sources is webp is enabled if supported but images are already webp' => [
                'mainImage' => $mainImageWebp,
                'mediumImage' => $mediumImageWebp,
                'smallImage' => $smallImageWebp,
                'imageVariantSettings' => [
                    'mainImage' => [
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
                        'srcset' => '/original_filter/main-image.webp',
                        'type' => 'image/webp',
                        'media' => '(max-width:9999px)',
                    ],
                ],
            ],
        ];
    }
}
