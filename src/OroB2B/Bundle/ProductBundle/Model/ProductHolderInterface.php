<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;

interface ProductHolderInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set product
     *
     * @param Product $product
     * @return ProductHolderInterface
     */
    public function setProduct(Product $product = null);

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct();

    /**
     * Set productSku
     *
     * @param string $productSku
     * @return ProductHolderInterface
     */
    public function setProductSku($productSku);

    /**
     * Get productSku
     *
     * @return string
     */
    public function getProductSku();
}
