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

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
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

    public function setProductUnit(?ProductUnit $productUnit): self
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): self
    {
        $this->price = $price;

        return $this;
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
