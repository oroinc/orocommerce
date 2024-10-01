<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Represents a product line item.
 */
class ProductLineItem implements ProductLineItemInterface, ProductLineItemsHolderAwareInterface
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

    protected ?string $unitCode = null;

    /**
     * @var float
     */
    protected $quantity = 1;

    private ?ProductLineItemsHolderInterface $lineItemsHolder = null;

    /**
     * @param mixed $identifier
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->identifier;
    }

    #[\Override]
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
        $this->unitCode = $unit->getCode();

        return $this;
    }

    public function setProductUnit(ProductUnit $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->getUnit();
    }

    #[\Override]
    public function getProductUnitCode()
    {
        return $this->unit ? $this->unit->getCode() : $this->unitCode;
    }

    #[\Override]
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

    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    #[\Override]
    public function getParentProduct()
    {
        return null;
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

    #[\Override]
    public function getProductSku()
    {
        return $this->product ? $this->product->getSku() : null;
    }

    #[\Override]
    public function getLineItemsHolder(): ?ProductLineItemsHolderInterface
    {
        return $this->lineItemsHolder;
    }

    public function setLineItemsHolder(?ProductLineItemsHolderInterface $lineItemsHolder): ProductLineItem
    {
        $this->lineItemsHolder = $lineItemsHolder;

        return $this;
    }
}
