<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('oro_cms_image_slide_sources', [$this, 'getImageSlideSources']),
            new TwigFunction('oro_cms_image_slide_image', [$this, 'getImageSlideImage']),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            AttachmentManager::class,
            'oro_cms.provider.image_slider_image_placeholder.default' => ImagePlaceholderProviderInterface::class,
            PropertyAccessorInterface::class,
            DoctrineHelper::class
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
        $imageTypes = ['smallImage', 'mediumImage', 'largeImage', 'extraLargeImage'];
        $sizes = ['', '2x', '3x'];

        $this->ensureImagesLoaded($imageSlide, $imageTypes, $sizes);

        $isWebpEnabledIfSupported = $this->getAttachmentManager()->isWebpEnabledIfSupported();
        foreach ($imageTypes as $imageType) {
            $imageVariants = $this->getImageVariants($imageType, $sizes);
            $mediaQuery = $imageVariantSettings[$imageType]['media'] ?? '';
            $mediaQuery = $mediaQuery ? ['media' => $mediaQuery] : [];
            $imageOptions = $imageVariantSettings[$imageType] ?? [];

            if ($isWebpEnabledIfSupported) {
                $webpSrcset = $this->getSrcset($imageType, $imageVariants, $imageOptions, $imageSlide, 'webp');
                if ($webpSrcset) {
                    $sources[] = $mediaQuery + ['srcset' => $webpSrcset, 'type' => 'image/webp'];
                }
            }

            $srcset = $this->getSrcset($imageType, $imageVariants, $imageOptions, $imageSlide);
            if ($srcset) {
                $data = ['srcset' => $srcset];
                $type = $this->getMimeType($imageVariants, $imageSlide);
                if ($type) {
                    $data['type'] = $type;
                }
                $sources[] = $mediaQuery + $data;
            }
        }

        return $sources;
    }

    public function getImageSlideImage(ImageSlide $imageSlide, string $format = ''): ?string
    {
        $image = $this->getImage($imageSlide, 'extraLargeImage');
        if (null !== $image) {
            return $this->getAttachmentManager()->getFilteredImageUrl($image, 'original', $format);
        }

        $image = $this->getFallbackImage($imageSlide, 'extraLargeImage');
        if (null !== $image) {
            return $this->getAttachmentManager()->getFilteredImageUrl($image, 'slider_extra_large', $format);
        }

        return $this->getImagePlaceholderProvider()->getPath('original', $format);
    }

    private function getSrcset(
        string $imageType,
        array $imageVariants,
        array $imageOptions,
        ImageSlide $imageSlide,
        $format = ''
    ): string {
        $srcset = [];
        foreach ($imageVariants as $size => $imageVariant) {
            $image = $this->getImage($imageSlide, $imageVariant);
            if (null !== $image) {
                $filterName = $imageOptions['filter' . $size] ?? 'original';
            } else {
                $image = $this->getFallbackImage($imageSlide, $imageType, $size);
                $filterName = $imageOptions['fallback_filter' . $size] ?? 'original';
            }
            if (null === $image) {
                continue;
            }

            if ('webp' === $format && $image->getExtension() === 'webp') {
                continue;
            }

            $src = $this->getAttachmentManager()->getFilteredImageUrl($image, $filterName, $format);
            if ($size) {
                $src .= ' ' . $size;
            }
            $srcset[] = $src;
        }

        return $srcset ? implode(', ', $srcset) : '';
    }

    private function getMimeType(array $imageVariants, ImageSlide $imageSlide): ?string
    {
        $mimeTypes = [];
        foreach ($imageVariants as $imageVariant) {
            $image = $this->getImage($imageSlide, $imageVariant);
            if (null !== $image) {
                $mimeTypes[] = $image->getMimeType();
            }
        }
        $mimeTypes = array_unique($mimeTypes);

        return \count($mimeTypes) === 1 ? reset($mimeTypes) : null;
    }

    /**
     * Tries to find fallback image for given size, available fallbacks
     * - 2x can be created from 3x
     * - 1x can be created from 3x or 2x
     */
    private function getFallbackImage(ImageSlide $imageSlide, string $imageType, string $size = ''): ?File
    {
        if ('' === $size) {
            return
                $this->getImage($imageSlide, $this->getImageVariant($imageType, '3x'))
                ?? $this->getImage($imageSlide, $this->getImageVariant($imageType, '2x'));
        }

        if ('2x' === $size) {
            return $this->getImage($imageSlide, $this->getImageVariant($imageType, '3x'));
        }

        return null;
    }

    private function getImage(ImageSlide $imageSlide, string $imageVariant): ?File
    {
        return $this->getPropertyAccessor()->getValue($imageSlide, $imageVariant);
    }

    private function getImageVariant(string $imageType, string $size): string
    {
        return $size ? $imageType . $size : $imageType;
    }

    private function getImageVariants(string $imageType, array $sizes): array
    {
        $imageVariants = [];
        foreach ($sizes as $size) {
            $imageVariants[$size] = $this->getImageVariant($imageType, $size);
        }

        return $imageVariants;
    }

    /**
     * Loads all images to avoid loading each image by a separate DB query.
     */
    private function ensureImagesLoaded(ImageSlide $imageSlide, array $imageTypes, array $sizes): void
    {
        $imageIds = [];
        foreach ($imageTypes as $imageType) {
            $imageVariants = $this->getImageVariants($imageType, $sizes);
            foreach ($imageVariants as $imageVariant) {
                $image = $this->getImage($imageSlide, $imageVariant);
                if ($image instanceof Proxy && !$image->__isInitialized()) {
                    $imageIds[] = $image->getId();
                }
            }
        }
        if ($imageIds) {
            $imageIds = array_unique($imageIds);
            sort($imageIds);
            // load entities into the entity manager
            $this->getDoctrine()->getRepository(File::class)->findBy(['id' => $imageIds]);
        }
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
            $this->propertyAccessor = $this->container->get(PropertyAccessorInterface::class);
        }

        return $this->propertyAccessor;
    }

    private function getDoctrine(): ManagerRegistry
    {
        return $this->container->get(ManagerRegistry::class);
    }
}
