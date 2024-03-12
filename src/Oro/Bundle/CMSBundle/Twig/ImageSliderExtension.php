<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to get picture sources for image slide for {@see ImageSlide} entity.
 */
class ImageSliderExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?AttachmentManager $attachmentManager = null;
    private ?ImagePlaceholderProviderInterface $imagePlaceholderProvider = null;
    private ?PropertyAccessorInterface $propertyAccessor = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_cms_image_slide_sources', [$this, 'getImageSlideSources']),
            new TwigFunction('oro_cms_image_slide_image', [$this, 'getImageSlideImage']),
        ];
    }

    public static function getSubscribedServices(): array
    {
        return [
            AttachmentManager::class,
            'oro_cms.provider.image_slider_image_placeholder.default' => ImagePlaceholderProviderInterface::class,
            'property_accessor' => PropertyAccessorInterface::class
        ];
    }

    /**
     * Returns sources array that can be used in <picture> tag of an image slide.
     * Adds WebP image variants is current oro_attachment.webp_strategy is "if_supported".
     *
     * @param ImageSlide $imageSlide
     * @param array $imageVariantSettings
     *  [
     *      'smallImage' => [
     *          'filter' => 'original', // filter for standard smallImage
     *          'fallback_filter' => 'width_123', // filter for 2x or 3x if standard is absent
     *          'filter2x' => 'original', // filter for smallImage 2x
     *          'fallback_filter2x' => 'width_246' // filter for 3x if 2x is absent
     *          'filter3x' => 'original', // filter for smallImage 3x
     *          'media' => 'max-width: 123px', // media query to add to <source> tag
     *      ],
     *      'mediumImage' => [
     *          'filter' => 'original', // filter for standard mediumImage
     *          'fallback_filter' => 'width_456', // filter for 2x or 3x if standard is absent
     *          'filter2x' => 'original', // filter for mediumImage 2x
     *          'fallback_filter2x' => 'width_912' // filter for 3x if 2x is absent
     *          'filter3x' => 'original', // filter for mediumImage 3x
     *          'media' => 'max-width: 456px', // media query to add to <source> tag
     *      ],
     *      'largeImage' => [
     *          'filter' => 'original', // filter for standard largeImage
     *      ],
     *      'extraLargeImage' => [
     *          'filter' => 'original', // filter for standard extraLargeImage
     *      ],
     *  ]
     *
     * @return array
     *  [
     *      [
     *          'srcset' => '/url/for/small-image.png',
     *          'type' => 'image/png',
     *          'media' => '(max-width: 123px)'
     *      ],
     *      [
     *          'srcset' => '/url/for/medium-image.jpg, /url/for/medium-image.jpg 2x, /url/for/medium-image.jpg 3x',
     *          'type' => 'image/jpg',
     *          'media' => '(max-width: 456px)'
     *      ],
     *      [
     *          'srcset' => '/url/for/large-image.png, /url/for/large-image-2x.png 2x',
     *          'type' => 'image/png',
     *      ],
     *      [
     *          'srcset' => '/url/for/large-image.png, /url/for/large-image-2x.webp 2x',
     *      ],
     *      // ...
     *  ]
     */
    public function getImageSlideSources(ImageSlide $imageSlide, array $imageVariantSettings): array
    {
        $sources = [];
        $isWebpEnabledIfSupported = $this->getAttachmentManager()->isWebpEnabledIfSupported();
        foreach (['smallImage', 'mediumImage', 'largeImage', 'extraLargeImage'] as $imageType) {
            $mediaQuery = $imageVariantSettings[$imageType]['media'] ?? '';
            $mediaQuery = $mediaQuery ? ['media' => $mediaQuery] : [];
            $imageOptions = $imageVariantSettings[$imageType] ?? [];

            if ($isWebpEnabledIfSupported) {
                $srcset = $this->getSrcset($imageType, $imageOptions, $imageSlide, 'webp');
                if ($srcset) {
                    $sources[] = $mediaQuery + [
                            'srcset' => $srcset,
                            'type' => 'image/webp',
                        ];
                }
            }
            $srcset = $this->getSrcset($imageType, $imageOptions, $imageSlide);
            if ($srcset) {
                $data = [
                    'srcset' => $srcset,
                ];
                $type = $this->getMimeType($imageType, $imageSlide);
                if ($type) {
                    $data['type'] = $type;
                }
                $sources[] = $mediaQuery + $data;
            }
        }

        return $sources;
    }

    public function getImageSlideImage(
        ImageSlide $imageSlide,
        string $format = ''
    ): ?string {
        $image = $this->getPropertyAccessor()->getValue($imageSlide, 'extraLargeImage');
        if ($image) {
            return $this->getAttachmentManager()->getFilteredImageUrl($image, 'original', $format);
        }

        $image = $this->getFallbackImage($imageSlide, 'extraLargeImage');
        if ($image) {
            return $this->getAttachmentManager()->getFilteredImageUrl($image, 'slider_extra_large', $format);
        }

        return $this->getImagePlaceholderProvider()->getPath('original', $format);
    }

    private function getSrcset(
        string $imageType,
        array $imageOptions,
        ImageSlide $imageSlide,
        $format = ''
    ): string {
        $sizes = ['', '2x', '3x'];
        $srcset = [];
        $propertyAccessor = $this->getPropertyAccessor();

        foreach ($sizes as $size) {
            $imageVariant = sprintf('%s%s', $imageType, $size);
            /** @var File $image */
            $image = $propertyAccessor->getValue($imageSlide, $imageVariant);
            if ($image) {
                $filterName = $imageOptions[sprintf('filter%s', $size)] ?? 'original';
            } else {
                $image = $this->getFallbackImage($imageSlide, $imageType, $size);
                $filterName = $imageOptions[sprintf('fallback_filter%s', $size)] ?? 'original';
            }

            if (!$image) {
                continue;
            }

            if ('webp' === $format && $image->getExtension() === 'webp') {
                continue;
            }

            $srcset[] = sprintf(
                '%s%s',
                $this->getAttachmentManager()->getFilteredImageUrl($image, $filterName, $format),
                $size ? sprintf(' %s', $size) : ''
            );
        }

        return !empty($srcset) ? implode(', ', $srcset) : '';
    }

    private function getMimeType(string $imageType, ImageSlide $imageSlide): ?string
    {
        $sizes = ['', '2x', '3x'];
        $mimeTypes = [];
        foreach ($sizes as $size) {
            $imageVariant = sprintf('%s%s', $imageType, $size);
            /** @var File $image */
            $image = $this->getPropertyAccessor()->getValue($imageSlide, $imageVariant);
            if ($image) {
                $mimeTypes[] = $image->getMimeType();
            }
        }

        $mimeTypes = array_unique($mimeTypes);

        return count($mimeTypes) == 1 ? reset($mimeTypes) : null;
    }

    /**
     * Tries to find fallback image for given size, available fallbacks
     * - 2x can be created from 3x
     * - 1x can be created from 3x or 2x
     */
    private function getFallbackImage(ImageSlide $imageSlide, string $imageType, string $size = ''): ?File
    {
        $fallback2x = $this->getPropertyAccessor()->getValue($imageSlide, sprintf('%s%s', $imageType, '2x'));
        $fallback3x = $this->getPropertyAccessor()->getValue($imageSlide, sprintf('%s%s', $imageType, '3x'));

        return match ($size) {
            default => null,
            '' => $fallback3x ?: $fallback2x ?: null,
            '2x' => $fallback3x ?: null,
        };
    }

    private function getAttachmentManager(): AttachmentManager
    {
        if (null === $this->attachmentManager) {
            $this->attachmentManager = $this->container->get(AttachmentManager::class);
        }

        return $this->attachmentManager;
    }

    private function getImagePlaceholderProvider(): ImagePlaceholderProviderInterface
    {
        if (null === $this->imagePlaceholderProvider) {
            $this->imagePlaceholderProvider = $this->container->get(
                'oro_cms.provider.image_slider_image_placeholder.default'
            );
        }

        return $this->imagePlaceholderProvider;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = $this->container->get('property_accessor');
        }

        return $this->propertyAccessor;
    }
}
