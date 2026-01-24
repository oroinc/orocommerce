<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Defines the contract for providing inventory quantity information.
 *
 * Implementations of this interface are responsible for retrieving available inventory
 * quantities for products and determining whether inventory can be decremented based on
 * product configuration and current inventory levels.
 */
interface InventoryQuantityProviderInterface
{
    /**
     * @param Product $product
     * @param ProductUnit $unit
     * @return int
     */
    public function getAvailableQuantity(Product $product, ProductUnit $unit);

    /**
     * @param Product|null $product
     * @return bool
     */
    public function canDecrement(?Product $product = null);
}
