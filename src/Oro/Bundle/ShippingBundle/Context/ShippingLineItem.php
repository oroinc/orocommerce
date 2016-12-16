<?php

namespace Oro\Bundle\ShippingBundle\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Symfony\Component\HttpFoundation\ParameterBag;

class ShippingLineItem extends ParameterBag implements ShippingLineItemInterface
{
    const FIELD_PRICE = 'price';
    const FIELD_PRODUCT = 'product';
    const FIELD_PRODUCT_HOLDER = 'product_holder';
    const FIELD_PRODUCT_SKU = 'product_sku';
    const FIELD_ENTITY_IDENTIFIER = 'entity_id';
    const FIELD_QUANTITY = 'quantity';
    const FIELD_PRODUCT_UNIT = 'product_unit';
    const FIELD_PRODUCT_UNIT_CODE = 'product_unit_code';
    const FIELD_WEIGHT = 'weight';
    const FIELD_DIMENSIONS = 'dimensions';

    /**
     * {@inheritDoc}
     */
    public function __construct(array $parameters)
    {
        parent::__construct($parameters);
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice()
    {
        return $this->get(self::FIELD_PRICE);
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {
        return $this->get(self::FIELD_PRODUCT);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductHolder()
    {
        return $this->get(self::FIELD_PRODUCT_HOLDER);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductSku()
    {
        return $this->get(self::FIELD_PRODUCT_SKU);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityIdentifier()
    {
        return $this->get(self::FIELD_ENTITY_IDENTIFIER);
    }

    /**
     * {@inheritDoc}
     */
    public function getQuantity()
    {
        return $this->get(self::FIELD_QUANTITY);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUnit()
    {
        return $this->get(self::FIELD_PRODUCT_UNIT);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductUnitCode()
    {
        return $this->get(self::FIELD_PRODUCT_UNIT_CODE);
    }

    /**
     * {@inheritDoc}
     */
    public function getWeight()
    {
        return $this->get(self::FIELD_WEIGHT);
    }

    /**
     * {@inheritDoc}
     */
    public function getDimensions()
    {
        return $this->get(self::FIELD_DIMENSIONS);
    }

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @param ProductHolderInterface $holder
     * @return $this
     */
    public function setProductHolder(ProductHolderInterface $holder = null)
    {
        $this->productHolder = $holder;
        return $this;
    }

    /**
     * @param ProductUnit $productUnit
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * @param $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @param Weight $weight
     * @return $this
     */
    public function setWeight(Weight $weight = null)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @param Dimensions $dimensions
     * @return $this
     */
    public function setDimensions(Dimensions $dimensions = null)
    {
        $this->dimensions = $dimensions;

        return $this;
    }
}
