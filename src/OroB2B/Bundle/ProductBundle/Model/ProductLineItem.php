<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductLineItem implements ProductLineItemInterface
{
    /**
     * @var mixed
     */
    protected $identifier;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ProductUnit
     */
    protected $unit;

    /**
     * @var float
     */
    protected $quantity = 1;

    /**
     * @param mixed $identifier
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getProductHolder()
    {
        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param ProductUnit $unit
     * @return $this
     */
    public function setUnit(ProductUnit $unit)
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUnit()
    {
        return $this->getUnit();
    }
    
    /**
     * {@inheritDoc}
     */
    public function getProductUnitCode()
    {
        return $this->unit ? $this->unit->getCode() : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProductSku()
    {
        return $this->product ? $this->product->getSku() : null;
    }
}
