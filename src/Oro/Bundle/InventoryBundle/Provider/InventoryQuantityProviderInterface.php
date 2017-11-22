<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

interface InventoryQuantityProviderInterface
{
    /**
     * @param Product $product
     * @param ProductUnit $unit
     * @return int
     */
    public function getAvailableQuantity(Product $product, ProductUnit $unit);

    /**
     * @param Product $product
     * @return bool
     */
    public function canDecrement(Product $product = null);
}
