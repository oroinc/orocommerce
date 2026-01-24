<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Defines the contract for entities representing related item relationships.
 *
 * Implementations of this interface represent associations between products,
 * such as related products or upsell products, providing standardized access
 * to both the source product and the related item.
 */
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
