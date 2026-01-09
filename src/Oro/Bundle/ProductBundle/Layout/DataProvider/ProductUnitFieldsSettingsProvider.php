<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;

/**
 * Provides product unit field visibility settings for layout rendering.
 *
 * This data provider exposes product unit field visibility logic to layout templates, allowing templates to determine
 * whether unit selection fields should be displayed for specific products based on configuration and business rules.
 */
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
