<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Defines the contract for entities that provide product-specific shipping options.
 *
 * Implementations of this interface provide access to shipping-related properties such as product weight, dimensions,
 * and unit of measure, which are used to calculate shipping costs and determine shipping method availability.
 */
interface ProductShippingOptionsInterface
{
    /**
     * @return Product|null
     */
    public function getProduct();

    /**
     * @return ProductUnit|null
     */
    public function getProductUnit();

    /**
     * @return Weight|null
     */
    public function getWeight();

    /**
     * @return Dimensions|null
     */
    public function getDimensions();
}
