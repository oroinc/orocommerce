<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Stub;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class LineItemPriceAwareStub implements ProductLineItemInterface, PriceAwareInterface
{
    /** @var null|int */
    private $id;

    /** @var null|Product */
    private $product;

    /** @var null|Product */
    private $parentProduct;

    /** @var null|ProductUnit */
    private $productUnit;

    /** @var null|float */
    private $quantity;

    /** @var null|Price */
    private $price;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentProduct(): ?Product
    {
        return $this->parentProduct;
    }

    public function setParentProduct(?Product $parentProduct): void
    {
        $this->parentProduct = $parentProduct;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnit(): ?ProductUnit
    {
        return $this->productUnit;
    }

    public function setProductUnit(?ProductUnit $productUnit): void
    {
        $this->productUnit = $productUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): void
    {
        $this->price = $price;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier(): ?int
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProductSku(): ?string
    {
        return $this->getProduct() ? $this->getProduct()->getSku() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode()
    {
        return $this->getProductUnit() ? $this->getProductUnit()->getCode() : null;
    }
}
