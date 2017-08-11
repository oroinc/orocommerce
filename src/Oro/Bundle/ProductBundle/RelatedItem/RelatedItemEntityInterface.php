<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

interface RelatedItemEntityInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return Product
     */
    public function getProduct();

    /**
     * @param Product $product
     * @return RelatedItemEntityInterface
     */
    public function setProduct(Product $product);

    /**
     * @return Product
     */
    public function getRelatedItem();

    /**
     * @param Product $product
     * @return RelatedItemEntityInterface
     */
    public function setRelatedItem(Product $product);
}
