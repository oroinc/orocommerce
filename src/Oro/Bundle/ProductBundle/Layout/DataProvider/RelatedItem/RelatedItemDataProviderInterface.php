<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

interface RelatedItemDataProviderInterface
{
    /**
     * @param Product $product
     * @return Product[]
     */
    public function getRelatedItems(Product $product);

    /**
     * @return bool
     */
    public function isSliderEnabled();

    /**
     * @return bool
     */
    public function isAddButtonVisible();
}
