<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class QuickAddProductInformation
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var float
     */
    protected $quantity;

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return QuickAddProductInformation
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return QuickAddProductInformation
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }
}
