<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

interface ProductLineItemInterface extends ProductHolderInterface, ProductUnitHolderInterface, QuantityAwareInterface
{
    /**
     * @return Product|null
     */
    public function getParentProduct();
}
