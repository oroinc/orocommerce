<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

/**
 * Provides image dimensions for product images based on their assigned types.
 *
 * This provider retrieves the configured dimensions for each image type associated with a product image,
 * enabling proper image resizing and display across the application.
 */
class ProductImagesDimensionsProvider
{
    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    public function __construct(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    /**
     * @param ProductImage $productImage
     * @return ThemeImageTypeDimension[]
     */
    public function getDimensionsForProductImage(ProductImage $productImage)
    {
        $dimensions = [];
        $allImageTypes = $this->imageTypeProvider->getImageTypes();

        foreach ($productImage->getTypes() as $imageType) {
            /** @var ProductImageType $imageType */
            $imageTypeName = $imageType->getType();
            if (isset($allImageTypes[$imageTypeName])) {
                $dimensions = array_merge($dimensions, $allImageTypes[$imageTypeName]->getDimensions());
            }
        }

        return $dimensions;
    }
}
