<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * A model for storing product price data.
 */
class ProductPriceCriteria
{
    protected Product $product;

    protected ProductUnit $productUnit;

    protected float $quantity;

    protected string $currency;

    private string $identifier;

    public function __construct(Product $product, ProductUnit $productUnit, float $quantity, string $currency)
    {
        if (!$product->getId()) {
            throw new \InvalidArgumentException('Product must have id.');
        }
        $this->product = $product;

        if (!$productUnit->getCode()) {
            throw new \InvalidArgumentException('ProductUnit must have code.');
        }
        $this->productUnit = $productUnit;

        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be numeric and more than or equal zero.');
        }
        $this->quantity = $quantity;

        if (!$currency) {
            throw new \InvalidArgumentException('Currency must be non-empty string.');
        }
        $this->currency = $currency;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getProductUnit(): ProductUnit
    {
        return $this->productUnit;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getIdentifier(): string
    {
        if (!isset($this->identifier)) {
            $this->identifier = sprintf(
                '%s-%s-%s-%s',
                $this->getProduct()->getId(),
                $this->getProductUnit()->getCode(),
                $this->getQuantity(),
                $this->getCurrency()
            );
        }

        return $this->identifier;
    }
}
