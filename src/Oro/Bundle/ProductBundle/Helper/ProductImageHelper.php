<?php

namespace Oro\Bundle\ProductBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

/**
 * Helper service to collect product image custom information
 */
class ProductImageHelper
{
    /**
     * @param ProductImage[]|Collection $productImages
     * @return array
     */
    public function countImagesByType(Collection $productImages): array
    {
        $imagesByTypeCounter = [];

        foreach ($productImages as $productImage) {
            foreach ($productImage->getTypes() as $type) {
                /** @var ProductImageType $type */
                $typeName = $type->getType();

                array_key_exists($typeName, $imagesByTypeCounter) ?
                    $imagesByTypeCounter[$typeName]++ :
                    $imagesByTypeCounter[$typeName] = 1;
            }
        }

        return $imagesByTypeCounter;
    }

    /**
     * Sorts product images in next order:
     *     - first element is the main image
     *     - second element is the listing image
     *     - other elements are sorted by id ascending
     *
     * @param array|ProductImage[] $productImages
     * @return ProductImage[]
     */
    public function sortImages(array $productImages): array
    {
        uasort($productImages, function (ProductImage $image1, ProductImage $image2) {
            if ($image1->hasType(ProductImageType::TYPE_MAIN)) {
                return -1;
            } elseif ($image2->hasType(ProductImageType::TYPE_MAIN)) {
                return 1;
            } elseif ($image1->hasType(ProductImageType::TYPE_LISTING)) {
                return -1;
            } elseif ($image2->hasType(ProductImageType::TYPE_LISTING)) {
                return 1;
            } else {
                return ($image1->getId() > $image2->getId()) ? 1 : -1;
            }
        });

        return $productImages;
    }
}
