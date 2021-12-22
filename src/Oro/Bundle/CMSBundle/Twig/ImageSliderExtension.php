<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to get picture sources for image slide for {@see ImageSlide} entity.
 */
class ImageSliderExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_cms_image_slide_sources', [$this, 'getImageSlideSources']),
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
     *          'filter' => 'original', // filter for smallImage
     *          'fallback_filter' => 'width_123', // filter for mainImage if smallImage is absent
     *          'media' => 'max-width: 123px', // media query to add to <source> tag
     *      ],
     *      'mediumImage' => [
     *          'filter' => 'original', // filter for mediumImage
     *          'fallback_filter' => 'width_456', // filter for mainImage if mediumImage is absent
     *          'media' => 'max-width: 456px', // media query to add to <source> tag
     *      ],
     *      'mainImage' => [
     *          'filter' => 'original', // filter for mainImage
     *      ],
     *  ]
     * @return array
     *  [
     *      [
     *          'srcset' => '/url/for/small-image.png',
     *          'type' => 'image/png',
     *          'media' => '(max-width: 123px)'
     *      ],
     *      [
     *          'srcset' => '/url/for/medium-image.png',
     *          'type' => 'image/png',
     *          'media' => '(max-width: 456px)'
     *      ],
     *      [
     *          'srcset' => '/url/for/main-image.png',
     *          'type' => 'image/png',
     *      ],
     *      // ...
     *  ]
     */
    public function getImageSlideSources(ImageSlide $imageSlide, array $imageVariantSettings): array
    {
        $sources = [];
        $isWebpEnabledIfSupported = $this->getAttachmentManager()->isWebpEnabledIfSupported();
        foreach (['smallImage', 'mediumImage', 'mainImage'] as $imageVariant) {
            $getImage = 'get' . ucfirst($imageVariant);
            $image = $imageSlide->$getImage();
            if ($image) {
                $filterName = $imageVariantSettings[$imageVariant]['filter'] ?? 'original';
            } else {
                $image = $imageSlide->getMainImage();
                $filterName = $imageVariantSettings[$imageVariant]['fallback_filter'] ?? 'original';
            }

            if (!$image) {
                continue;
            }

            $mediaQuery = $imageVariantSettings[$imageVariant]['media'] ?? '';
            $mediaQuery = $mediaQuery ? ['media' => $mediaQuery] : [];

            if ($isWebpEnabledIfSupported && $image->getExtension() !== 'webp') {
                $sources[] = $mediaQuery + [
                        'srcset' => $this->getAttachmentManager()->getFilteredImageUrl($image, $filterName, 'webp'),
                        'type' => 'image/webp',
                    ];
            }
            $sources[] = $mediaQuery + [
                'srcset' => $this->getAttachmentManager()->getFilteredImageUrl($image, $filterName),
                'type' => $image->getMimeType(),
            ];
        }

        return $sources;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            AttachmentManager::class,
        ];
    }

    private function getAttachmentManager(): AttachmentManager
    {
        return $this->container->get(AttachmentManager::class);
    }
}
