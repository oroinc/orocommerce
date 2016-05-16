<?php

namespace OroB2B\Bundle\ShippingBundle\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Model\Weight;

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
