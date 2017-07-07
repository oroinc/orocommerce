<?php

namespace Oro\Bundle\ProductBundle\Helper\ProductGrouper;

use Doctrine\Common\Collections\ArrayCollection;

interface ProductsGrouperInterface
{
    /**
     * Groups array/ArrayCollection of products by SKU and Unit
     *
     * @param array|ArrayCollection $products
     * @return array|ArrayCollection
     */
    public function process($products);
}
