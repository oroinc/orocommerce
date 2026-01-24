<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Defines the contract for entities that hold a reference to a product.
 *
 * Implementations of this interface represent entities that are associated with a product, such as line items,
 * requests, or other product-related data structures, providing standardized access to the product and its SKU.
 */
interface ProductHolderInterface
{
    /**
     * Get id
     *
     * @return mixed
     */
    public function getEntityIdentifier();

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct();

    /**
     * Get productSku
     *
     * @return string
     */
    public function getProductSku();
}
