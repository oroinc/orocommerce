<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

interface SkuIncrementorInterface
{
    /**
     * @param string $sku
     * @return string
     */
    public function increment($sku);
}
