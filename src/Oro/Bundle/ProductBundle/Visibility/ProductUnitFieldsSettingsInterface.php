<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

interface ProductUnitFieldsSettingsInterface
{
    /**
     * @param Product $product
     * @return bool
     */
    public function isProductUnitSelectionVisible(Product $product);

    /**
     * @param Product $product
     * @return bool
     */
    public function isProductPrimaryUnitVisible(Product $product = null);

    /**
     * @param Product $product
     * @return bool
     */
    public function isAddingAdditionalUnitsToProductAvailable(Product $product = null);

    /**
     * @param Product $product
     * @return ProductUnit[]
     */
    public function getAvailablePrimaryUnitChoices(Product $product = null);
}
