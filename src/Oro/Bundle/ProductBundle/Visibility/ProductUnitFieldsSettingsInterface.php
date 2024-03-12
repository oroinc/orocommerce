<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Abstraction for product single unit mode.
 */
interface ProductUnitFieldsSettingsInterface
{
    public function isProductUnitSelectionVisible(Product $product): bool;

    public function isProductPrimaryUnitVisible(?Product $product = null): bool;

    public function isAddingAdditionalUnitsToProductAvailable(?Product $product = null): bool;

    /**
     * @return ProductUnit[]
     */
    public function getAvailablePrimaryUnitChoices(?Product $product = null): array;
}
