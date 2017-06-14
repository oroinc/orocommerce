<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class BasicShippingLineItemBuilder implements ShippingLineItemBuilderInterface
{
    /**
     * @var Price
     */
    private $price;

    /**
     * @var ProductUnit
     */
    private $unit;

    /**
     * @var string
     */
    private $unitCode;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var ProductHolderInterface
     */
    private $productHolder;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var string
     */
    private $productSku;

    /**
     * @var Dimensions
     */
    private $dimensions;

    /**
     * @var Weight
     */
    private $weight;

    /**
     * @param ProductUnit $unit
     * @param string $unitCode
     * @param int $quantity
     * @param ProductHolderInterface $productHolder
     */
    public function __construct(
        ProductUnit $unit,
        $unitCode,
        $quantity,
        ProductHolderInterface $productHolder
    ) {
        $this->unit = $unit;
        $this->unitCode = $unitCode;
        $this->quantity = $quantity;
        $this->productHolder = $productHolder;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $params = [
            ShippingLineItem::FIELD_PRODUCT_UNIT => $this->unit,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $this->unitCode,
            ShippingLineItem::FIELD_QUANTITY => $this->quantity,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $this->productHolder,
            ShippingLineItem::FIELD_ENTITY_IDENTIFIER => $this->productHolder->getEntityIdentifier(),
        ];

        if (null !== $this->product) {
            $params[ShippingLineItem::FIELD_PRODUCT] = $this->product;
        }

        if (null !== $this->productSku) {
            $params[ShippingLineItem::FIELD_PRODUCT_SKU] = $this->productSku;
        }

        if (null !== $this->dimensions) {
            $params[ShippingLineItem::FIELD_DIMENSIONS] = $this->dimensions;
        }

        if (null !== $this->weight) {
            $params[ShippingLineItem::FIELD_WEIGHT] = $this->weight;
        }

        if (null !== $this->price) {
            $params[ShippingLineItem::FIELD_PRICE] = $this->price;
        }

        return new ShippingLineItem($params);
    }

    /**
     * {@inheritDoc}
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setProductSku($sku)
    {
        $this->productSku = $sku;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDimensions(Dimensions $dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setWeight(Weight $weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;

        return $this;
    }
}
