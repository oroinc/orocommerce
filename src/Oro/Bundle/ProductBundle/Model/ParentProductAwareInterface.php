<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Interface for a line item aware of its parent product.
 */
interface ParentProductAwareInterface
{
    /**
     * @return Product|null
     */
    public function getParentProduct();
}
