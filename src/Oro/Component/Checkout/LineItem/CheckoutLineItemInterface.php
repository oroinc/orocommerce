<?php

namespace Oro\Component\Checkout\LineItem;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

interface CheckoutLineItemInterface
{
    /**
     * @return Product
     */
    public function getProduct();

    /**
     * @return string
     */
    public function getProductSku();

    /**
     * @return ProductUnit
     */
    public function getProductUnit();

    /**
     * @return string
     */
    public function getProductUnitCode();

    /**
     * @return Product|null
     */
    public function getParentProduct();

    /**
     * @return float
     */
    public function getQuantity();
}
