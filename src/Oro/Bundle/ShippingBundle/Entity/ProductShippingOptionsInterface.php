<?php

namespace Oro\Bundle\ShippingBundle\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

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
