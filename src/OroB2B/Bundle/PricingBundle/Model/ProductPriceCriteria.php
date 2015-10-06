<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceCriteria
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
     * @var string
     */
    protected $currency;

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param float $quantity
     * @param string $currency
     */
    public function __construct(Product $product, ProductUnit $productUnit, $quantity, $currency)
    {
        if (!$product->getId()) {
            throw new \InvalidArgumentException('Product must have id.');
        }
        $this->product = $product;

        if (!$productUnit->getCode()) {
            throw new \InvalidArgumentException('ProductUnit must have code.');
        }
        $this->productUnit = $productUnit;

        if (!is_numeric($quantity) || $quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be numeric and more than or equal zero.');
        }
        $this->quantity = $quantity;

        if (strlen($currency) === 0) {
            throw new \InvalidArgumentException('Currency must be non-empty string.');
        }
        $this->currency = $currency;
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

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return sprintf(
            '%s-%s-%s-%s',
            $this->getProduct()->getId(),
            $this->getProductUnit()->getCode(),
            $this->getQuantity(),
            $this->getCurrency()
        );
    }
}
