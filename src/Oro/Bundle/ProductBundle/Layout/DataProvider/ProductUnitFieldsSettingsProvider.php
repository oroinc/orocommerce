<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;

class ProductUnitFieldsSettingsProvider
{
    /**
     * @var ProductUnitFieldsSettingsInterface
     */
    private $productUnitFieldsSettings;

    public function __construct(ProductUnitFieldsSettingsInterface $productUnitFieldsSettings)
    {
        $this->productUnitFieldsSettings = $productUnitFieldsSettings;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isProductUnitSelectionVisible(Product $product)
    {
        return $this->productUnitFieldsSettings->isProductUnitSelectionVisible($product);
    }
}
