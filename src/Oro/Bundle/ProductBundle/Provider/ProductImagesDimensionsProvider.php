<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

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
