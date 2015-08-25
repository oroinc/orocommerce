<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitQuantity
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ProductUnit
     */
    protected $productUnit;

    /**
     * @var float
     */
    protected $quantity;

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param float $quantity
     */
    public function __construct(Product $product, ProductUnit $productUnit, $quantity)
    {
        $this->product = $product;
        $this->productUnit = $productUnit;

        if ((!is_float($quantity) && !is_int($quantity)) || $quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive float or integer.');
        }

        $this->quantity = $quantity;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
