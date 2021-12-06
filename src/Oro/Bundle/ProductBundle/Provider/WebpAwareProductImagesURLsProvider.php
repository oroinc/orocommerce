<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Provides product images WebP urls.
 */
class WebpAwareProductImagesURLsProvider extends ProductImagesURLsProvider
{
    protected function getFilteredImageUrls(
        ProductImage $productImage,
        array $filtersNames,
        string $initialImageType
    ): array {
        $image = parent::getFilteredImageUrls($productImage, $filtersNames, $initialImageType);

        if ($this->attachmentManager->isWebpEnabledIfSupported()) {
            foreach ($filtersNames as $filterName) {
                array_unshift(
                    $image[$filterName],
                    [
                        'srcset' => $this->attachmentManager
                            ->getFilteredImageUrl($productImage->getImage(), $filterName, 'webp'),
                        'type' => 'image/webp',
                    ]
                );
            }
        }

        return $image;
    }
}
