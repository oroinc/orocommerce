<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

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
