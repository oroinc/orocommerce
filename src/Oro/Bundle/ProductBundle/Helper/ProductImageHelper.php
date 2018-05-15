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
    /** @var  string $productImageImportDir */
    protected $productImageImportDir;

    /**
     * @param ProductImage[]|Collection $productImages
     * @return array
     */
    public function countImagesByType(Collection $productImages)
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
     * @param string $productImageImportDir
     */
    public function setProductImageImportDir(string $productImageImportDir)
    {
        $this->productImageImportDir = $productImageImportDir;
    }

    /**
     * @return string
     */
    public function getProductImageImportDir()
    {
        return $this->productImageImportDir;
    }
}
