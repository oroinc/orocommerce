<?php

namespace Oro\Bundle\ProductBundle\Duplicator;

/**
 * Defines the contract for generating unique SKUs for duplicated products.
 *
 * Implementations should provide a strategy for incrementing product SKUs to ensure that duplicated products
 * receive unique identifiers while maintaining a logical relationship to the original SKU.
 */
interface SkuIncrementorInterface
{
    /**
     * Increments provided product SKU for a duplicated product.
     * The simplest example would be to increment
     * numeric suffix: "ABC-1" => "ABC-2".
     *
     * @param string $sku
     * @return string
     */
    public function increment($sku);
}
