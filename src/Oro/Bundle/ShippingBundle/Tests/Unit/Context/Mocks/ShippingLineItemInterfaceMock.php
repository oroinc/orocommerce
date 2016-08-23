<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\Mocks;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class ShippingLineItemInterfaceMock implements ShippingLineItemInterface
{
    const PRODUCT_SKU = 'test sku';
    const PRODUCT_UNIT_CODE = 'kg';
    const PRODUCT_PRECISION = 3;
    const WEIGHT = 1;
    const QUANTITY = 2;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var ProductUnit
     */
    private $productUnit;

    /**
     * @var Price
     */
    private $price;

    /**
     * @var Weight
     */
    private $weight;

    /**
     * @var Dimensions
     */
    private $dimensions;

    /**
     * @var int
     */
    private $quantity;

    /**
     * ProductHolderInterfaceMock constructor.
     */
    public function __construct()
    {
        $this->product = new Product();
        $this->product->setSku(self::PRODUCT_SKU);
        $this->productUnit = new ProductUnit();
        $this->productUnit->setCode(self::PRODUCT_UNIT_CODE)->setDefaultPrecision(self::PRODUCT_PRECISION);
        $this->price = new Price();
        $this->weight = (new Weight())->setValue(self::WEIGHT);
        $this->dimensions = new Dimensions();
        $this->quantity = self::QUANTITY;
    }

    /**
     * Get id
     *
     * @return mixed
     */
    public function getEntityIdentifier()
    {
        return 1;
    }

    /**
     * Get product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Get productSku
     *
     * @return string
     */
    public function getProductSku()
    {
        return $this->product->getSku();
    }

    /**
     * Get productHolder
     *
     * @return ProductHolderInterface
     */
    public function getProductHolder()
    {
        return null;
    }

    /**
     * Get product
     *
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * Get productUnitCode
     *
     * @return string
     */
    public function getProductUnitCode()
    {
        return $this->productUnit->getCode();
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;
    }

    /**
     * @return Weight|null
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return Dimensions|null
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
