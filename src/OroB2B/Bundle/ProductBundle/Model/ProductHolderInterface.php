<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;

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
